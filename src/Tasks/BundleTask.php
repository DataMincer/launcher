<?php

namespace DataMincerLauncher\Task;

use Symfony\Component\Yaml\Yaml;
use TaskRunner\Task;
use DataMincerLauncher\App;
use TaskRunner\TaskRunnerException;
use YamlElc\Dimension;

/**
 * @property App $app
 */
class BundleTask extends Task {

  protected static $taskId = 'bundle';

  /**
   * @throws TaskRunnerException
   */
  public function run() {
    $result = [];
    $yaml_indent = 2;
    $dm = $this->app->manager();
    $verbose = $this->options['verbose'] <= 2 ? $this->options['verbose'] : 2;

    if ($this->options['help']) {
      $this->logger->info("Available tasks:");
      $this->logger->info("info\n");
      $this->logger->info("\t" . "Lists available bundles and their details.");
    }
    else {
      $task_name = $this->options['task'];
      if ($task_name !== 'info') {
        throw new TaskRunnerException("Bundle task '$task_name' is not defined.");
      }
      switch ($verbose) {
        case 0:
          foreach ($dm->getBundles() as $bundle) {
            $result['Bundles'][] = $bundle->name();
          }
          break;
        case 1:
          $yaml_indent = 3;
          foreach ($dm->getBundles() as $bundle) {
            $result['Bundles'][$bundle->name()]['Dimensions'] = array_keys($bundle->getConfig()->getDimensions());
            $result['Bundles'][$bundle->name()]['Decks'] = $dm->getUnits($bundle->name())->count();
          }
          break;
        case 2:
          $yaml_indent = 5;
          foreach ($dm->getBundles() as $bundle) {
            /** @var Dimension $dimension */
            foreach ($bundle->getConfig()->getDimensions() as $dimension) {
              foreach ($dimension->getInfo() as $register => $domain_info) {
                foreach ($domain_info as $domain => $values_info) {
                  $result['Bundles'][$bundle->name()]['Dimensions'][$dimension->name()][$this->renderRegister($register) . $domain] = $this->renderValues($values_info);
                }
              }
            }
            $result['Bundles'][$bundle->name()]['Decks Count'] = $dm->getUnits($bundle->name())->count();
            foreach ($dm->getUnits($bundle->name()) as $id => $deck) {
              $result['Bundles'][$bundle->name()]['Decks'][] = $deck->id(TRUE);
            }
          }
          break;
        default:
          throw new TaskRunnerException("Unknown verbose option.");
      }
      $this->logger->info(Yaml::dump($result, $yaml_indent));
    }
  }

  protected function renderValues($values_info) {
    $result = [];
    foreach ($values_info as $value_info) {
      $result[] = '[' . implode(', ', $value_info['values']) . ']' . $this->renderConditions($value_info['conditions']);
    }
    return implode(', ', $result);
  }

  protected function renderConditions(array $conditions) {
    $lines = [];
    foreach ($conditions as $condition) {
      foreach ($condition as $dimension_name => $register_info) {
        foreach ($register_info as $register => $domain_info) {
          foreach ($domain_info as $domain => $values) {
            $lines[$dimension_name][] = implode(',', array_map(function($v) use ($register, $domain) {
              return $this->renderRegister($register) . $domain . '.' . $v;
            }, $values));
          }
        }
      }
    }
    $output = [];
    foreach ($lines as $dimension => $items) {
      $output[] = $dimension . '=' . implode(',', $items);
    }
    return $output ? ' (' . implode('; ', $output) . ')' : '';
  }

  protected function renderRegister($register) {
    $register_num = intval(substr($register, 1));
    return str_repeat(':', $register_num);
  }

}
