<?php

namespace DataMincerLauncher\Task;

use Exception;
use TaskRunner\Task;
use DataMincerCore\Plugin\PluginUnitInterface;
use DataMincerLauncher\App;

/**
 * @property App $app
 */
class DeckTask extends Task {

  protected static $taskId = 'deck';

  /**
   * @throws Exception
   */
  public function run() {
    $dm = $this->app->deckManager();

    if ($this->options['help']) {
      $tasks = [];
      foreach ($dm->getBundles() as $bundle_name => $bundle) {
        foreach($dm->getUnits($bundle_name) as $id => $deck) {
          foreach ($deck->getTasks() as $task_name => $task_info) {
            $tasks[$task_name]['help'] = $task_info['help'];
            $tasks[$task_name]['decks'][] = $deck->id(TRUE);
            $tasks[$task_name]['bundles'][] = $bundle_name;
          }
        }
      }
      if (count($tasks)) {
        $this->logger->msg('Available tasks: ');
      }
      foreach ($tasks as $task_name => $task_info) {
        $this->logger->msg($task_name);
        $this->logger->msg("\t" . $task_info['help']);
        $this->logger->msg("\tProvided by: " . count($task_info['decks']) . " decks in bundle(s): " . implode(", ", array_unique($task_info['bundles'])));
      }
    }
    else {
      $task_name = $this->options['task'];
      $deck_id = $this->options['deckId'];
      $triggered = FALSE;
      $no_bundles = TRUE;
      foreach ($dm->getBundles() as $bundle_name => $bundle) {
        $no_bundles = FALSE;
        /** @var PluginUnitInterface $deck */
        foreach ($dm->getUnits($bundle_name) as $id => $deck) {
          if ($deck_id && ($deck_id == $deck->id() || $deck_id == $deck->id(TRUE)) || is_null($deck_id)) {
            $triggered = TRUE;
            if (($task = $deck->getTask($task_name)) === FALSE) {
              throw new Exception("Deck task '$task_name' on deck '{$deck->id(TRUE)}' is not defined.");
            }
            $this->logger->msg("Bundle: $bundle_name, Task: $task_name, Deck: {$deck->id(TRUE)}, Origin: {$deck->getSummary()}");
            $res = FALSE;
            try {
              $res = call_user_func([$deck, $task['method']], $this->options['taskParams'], $this->options['verbose']);
            } catch (Exception $e) {
              $error = $e->getMessage();
            }
            if ($res === FALSE) {
              throw new Exception("Error executing task '$task_name' on deck '{$deck->id(TRUE)}'" . (!empty($error) ? "\n" . $error : ""));
            }
            if ($deck_id && $triggered) {
              // No need to run the rest if deck is specified
              break;
            }
          }
        }
        if ($deck_id && $triggered) {
          break;
        }
      }
      if ($no_bundles) {
        $this->logger->warn("No bundles found. Check your filters.");
      }
    }
  }

}
