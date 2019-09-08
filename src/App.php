<?php

namespace DataMincerLauncher;

use Exception;
use DataMincerCore\Manager;
use TaskRunner\LoggerInterface;

class App extends \TaskRunner\App {

  /** @var Manager */
  protected $manager;

  protected static $paramsMap = [
    'basePath' => '--base',
    'buildPath' => '--build',
    'filters' => '--filter',
    'novalidate' => '--novalidate',
    'verbose' => '-v',
    'debug' => '--debug',
    'timer' => '--timer',
    'unitId' => '--unit',
    'bundle' => 'bundle',
    'unit' => 'unit',
    'task' => 'TASK',
    'taskParams' => 'PARAMS',
    'help' => 'help'
  ];

  /** @var LoggerInterface */
  protected $logger;

  public function __construct($params = []) {
    parent::__construct($params);
    try {
      $this->manager = new Manager($this->options, $this->logger);
    }
    catch(Exception $e) {
      $this->logger()->err($e->getMessage());
      die(1);
    }
  }

  public function manager() {
    return $this->manager;
  }

  protected function getUsageDefinition() {
    return <<<TXT
DataMincer Launcher

Usage:
  dm-dm (bundle | unit [--unit=DECK]) 
    (help | TASK [PARAMS...])
    [options] 
    [-v...]
    [--debug] 
    [--filter=FILTER...] 

Commands:
  bundle                Lists available bundles and their details.
  bundle TASK           Execute task TASK on available bundles.
  bundle help           List defined tasks.
  unit                  Lists available units and their details.
  unit TASK             Execute task TASK on available units.
  unit help             List defined tasks.

Options:
  --base=PATH           Path to the dir with unit bundles data. [Default: ./bundles]
  --build=PATH          Path to the dir used to build units. [Default: ./build]
  --unit=DECK           Limit operations down to the single unit.
  -v...                 Show more details when running some tasks. Repeating may increase verbosity.
  --novalidate          Don't validate schemas (speeds up execution, but may rise unhandled exceptions).
  --debug               Show debugging information when running some tasks.
  --timer               Enable timer.
  --filter=FILTER...    Filter the units to work with. Represents a list in the following format:

                          FILTER ::= [BUNDLE][:SELECTOR[;SELECTOR...]]
                          SELECTOR ::= SELECTOR(i)[(:SELECTOR(i+1))...]
                          SELECTOR(i) ::= (DIMENSION=DOMAIN-Ri[.VALUE][,DOMAIN-Ri[.VALUE]...])

                        Here "i" starts at "0" and represents domain's register index. The list of 
                        domains and their definitions are read from the BUNDLE's bundle.yml file. 

                        Given this bundle.yml for a 'basic-numbers' bundle:

                          lang/[]:
                            en:  ["en-US", "en-GB"]
                            fr:  ["fr-FR"]
                            :fr: ["fr-CA"]

                          deck/<>:
                            numbers: ["10-100", "1000-2000"]
                            letters: ["a-z"]

                        the following invocations are possible:

                          basic-numbers:lang=en
                            - Select units which primary language is "en" (both British or American).

                          basic-numbers:lang=en.en-US
                            - Select units which primary language is "en" (American English).

                          basic-numbers:lang=en:fr
                            - Select units which primary language is "en" and native language is "fr".

                          basic-numbers:deck=numbers
                            - Select units from dimension "deck", domain "numbers".

                          basic-numbers:lang=:ru\;deck=numbers
                            - Select units from dimension "deck" domain "numbers" and having "ru" as native language.

                          basic-numbers:lang=en,fr
                            - Select units having primary language "en" or "fr"
TXT;
  }

}
