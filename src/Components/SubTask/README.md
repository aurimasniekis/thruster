Thruster SubTask Component
============================================

The SubTask Component provides a Javascript Promises style asynchronous task running with support for result, progress, error callbacks.


Usage
-----

### Running 4 parallel tasks with progress report

```php
<?php

declare (ticks = 1);

require_once __DIR__ . '/vendor/autoload.php';

use Thruster\Components\Manager\Manager;

$manager = new Manager();

$subTaskManager = $manager->newSubTaskManager();

for ($i = 0; $i < 4; $i++) {
    $subTaskManager->launchSubTask(
        function ($progress) {
            for ($i = 0; $i < 10; $i++) {
                sleep(1);
                $progress($i);
            }

            return 'done';
        },
        function ($result) use ($i) {
            echo 'Finished ' . $i . ': "' . $result . '"' . PHP_EOL;
        },
        function () {

        },
        function ($progress) use ($i) {
            echo 'Progress ' . $i . ': ' . $progress . PHP_EOL;
        }
    );
}

$subTaskManager->waitAll();
```

### Queue 4 task and then run all together

```php
<?php

declare (ticks = 1);

require_once __DIR__ . '/vendor/autoload.php';

use Thruster\Components\Manager\Manager;

$manager = new Manager();

$subTaskManager = $manager->newSubTaskManager();

for ($i = 0; $i < 4; $i++) {
    $subTaskManager->addSubTask(
        function ($progress) {
            for ($i = 0; $i < 10; $i++) {
                sleep(1);
                $progress($i);
            }

            return 'done';
        },
        function ($result) use ($i) {
            echo 'Finished ' . $i . ': "' . $result . '"' . PHP_EOL;
        },
        function () {

        },
        function ($progress) use ($i) {
            echo 'Progress ' . $i . ': ' . $progress . PHP_EOL;
        }
    );
}

$subTaskManager->runAll();
$subTaskManager->waitAll();
```