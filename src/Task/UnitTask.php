<?php

namespace DataMincerLauncher\Task;

use Exception;
use TaskRunner\Task;
use DataMincerCore\Plugin\PluginUnitInterface;
use DataMincerLauncher\App;

/**
 * @property App $app
 */
class UnitTask extends Task {

  protected static $taskId = 'unit';

  /**
   * @throws Exception
   */
  public function run() {
    $manager = $this->app->manager();

    if ($this->options['help']) {
      $tasks = [];
      foreach ($manager->getBundles() as $bundle_name => $bundle) {
        foreach($manager->getUnits($bundle_name) as $id => $unit) {
          foreach ($unit->getTasks() as $task_name => $task_info) {
            $tasks[$task_name]['help'] = $task_info['help'];
            $tasks[$task_name]['units'][] = $unit->id(TRUE);
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
        $this->logger->msg("\tProvided by: " . count($task_info['units']) . " units in bundle(s): " . implode(", ", array_unique($task_info['bundles'])));
      }
    }
    else {
      $task_name = $this->options['task'];
      $unit_id = $this->options['unitId'];
      $triggered = FALSE;
      $no_bundles = TRUE;
      foreach ($manager->getBundles() as $bundle_name => $bundle) {
        $no_bundles = FALSE;
        /** @var PluginUnitInterface $unit */
        foreach ($manager->getUnits($bundle_name) as $id => $unit) {
          if ($unit_id && ($unit_id == $unit->id() || $unit_id == $unit->id(TRUE)) || is_null($unit_id)) {
            $triggered = TRUE;
            if (($task = $unit->getTask($task_name)) === FALSE) {
              throw new Exception("Unit task '$task_name' on unit '{$unit->id(TRUE)}' is not defined.");
            }
            $this->logger->msg("Bundle: $bundle_name, Task: $task_name, Unit: {$unit->id(TRUE)}, Origin: {$unit->getSummary()}");
            $res = FALSE;
            try {
              $res = call_user_func([$unit, $task['method']], $this->options['taskParams'], $this->options['verbose']);
            } catch (Exception $e) {
              $error = $e->getMessage();
            }
            if ($res === FALSE) {
              throw new Exception("Error executing task '$task_name' on unit '{$unit->id(TRUE)}'" . (!empty($error) ? "\n" . $error : ""));
            }
            if ($unit_id && $triggered) {
              // No need to run the rest if unit is specified
              break;
            }
          }
        }
        if ($unit_id && $triggered) {
          break;
        }
      }
      if ($no_bundles) {
        $this->logger->warn("No bundles found. Check your filters.");
      }
    }
  }

}
