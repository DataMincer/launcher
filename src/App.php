<?php

namespace DataMincerLauncher;

use Exception;
use DataMincerCore\Manager;
use TaskRunner\LoggerInterface;

class App extends \TaskRunner\App {

  /** @var Manager */
  protected $deckManager;

  protected static $paramsMap = [
    'basePath' => '--base',
    'buildPath' => '--build',
    'filters' => '--filter',
    'novalidate' => '--novalidate',
    'verbose' => '-v',
    'debug' => '--debug',
    'timer' => '--timer',
    'deckId' => '--deck',
    'bundle' => 'bundle',
    'deck' => 'deck',
    'task' => 'TASK',
    'taskParams' => 'PARAMS',
    'help' => 'help'
  ];

  /** @var LoggerInterface */
  protected $logger;

  public function __construct() {
    parent::__construct();
    try {
      $this->deckManager = new Manager($this->options, $this->logger);
    }
    catch(Exception $e) {
      $this->logger()->err($e->getMessage());
      die(1);
    }
  }

  public function deckManager() {
    return $this->deckManager;
  }

  protected function getUsageDefinition() {
    return <<<TXT
Ultimate Audition: Deck Manager

Usage:
  dm-dm (bundle | deck [--deck=DECK]) 
    (help | TASK [PARAMS...])
    [options] 
    [-v...]
    [--debug] 
    [--filter=FILTER...] 

Commands:
  bundle                Lists available bundles and their details.
  bundle TASK           Execute task TASK on available bundles.
  bundle help           List defined tasks.
  deck                  Lists available decks and their details.
  deck TASK             Execute task TASK on available decks.
  deck help             List defined tasks.

Options:
  --base=PATH           Path to the dir with deck bundles data. [Default: ./]
  --build=PATH          Path to the dir used to build decks. [Default: ./build]
  --deck=DECK           Limit operations down to the single deck.
  -v...                 Show more details when running some tasks. Repeating may increase verbosity.
  --novalidate          Don't validate schemas (speeds up execution, but may rise unhandled exceptions).
  --debug               Show debugging information when running some tasks.
  --timer               Enable timer.
  --filter=FILTER...    Filter the decks to work with. Represents a list in the following format:

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
                            - Select decks which primary language is "en" (both British or American).

                          basic-numbers:lang=en.en-US
                            - Select decks which primary language is "en" (American English).

                          basic-numbers:lang=en:fr
                            - Select decks which primary language is "en" and native language is "fr".

                          basic-numbers:deck=numbers
                            - Select decks from dimension "deck", domain "numbers".

                          basic-numbers:lang=:ru\;deck=numbers
                            - Select decks from dimension "deck" domain "numbers" and having "ru" as native language.

                          basic-numbers:lang=en,fr
                            - Select decks having primary language "en" or "fr"
TXT;
  }

}
