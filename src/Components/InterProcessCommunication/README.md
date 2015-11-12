Thruster InterProcessCommunication Component
============================================

The InterProcessCommunication Component provides a way to communicate between master process and forked processes.

Currently Available Drivers:
----------------------------

* Socket Pair

Usage
-----

#### Single Connection

```php
<?php

declare (ticks = 1);

require_once __DIR__ . '/vendor/autoload.php';

use Thruster\Components\InterProcessCommunication\Connection;
use Thruster\Components\InterProcessCommunication\Packet;
use Thruster\Components\PosixSignalHandler\SignalHandler;
use Thruster\Wrappers\ProcessControl\ProcessControl;

$signalHandler = new SignalHandler();
$processControl = new ProcessControl();

$connection = new Connection();
$connection->setSignalHandler($signalHandler);
$connection->initialize();

$connection->onPacketReceived(function ($packet) {
    echo 'Slave: ' . $packet->getContent() . PHP_EOL;
});

$pid = $processControl->fork();

if ($pid > 0) {
    $connection->setMasterMode();
    $processControl->wait($status);
} else {
    $connection->setSlaveMode();
    sleep(1);

    $packet = new Packet();
    $packet->setContent('Hello world');
    $connection->sendPacket($packet);
}
```

#### Single connection with Packet Handler

```php
<?php

declare (ticks = 1);

require_once __DIR__ . '/vendor/autoload.php';

use Thruster\Components\InterProcessCommunication\PacketHandler;
use Thruster\Components\InterProcessCommunication\Connection;
use Thruster\Components\InterProcessCommunication\Packet;
use Thruster\Components\PosixSignalHandler\SignalHandler;
use Thruster\Wrappers\ProcessControl\ProcessControl;

$signalHandler = new SignalHandler();
$processControl = new ProcessControl();
$packetHandler = new PacketHandler();

$packetHandler->addHandler('custom_type', function($packet) {
    echo "Slave: " . $packet->getContent() . PHP_EOL;
});

$connection = new Connection();
$connection->setSignalHandler($signalHandler);
$connection->initialize();

$packetHandler->addProvider($connection);

$pid = $processControl->fork();

if ($pid > 0) {
    $connection->setMasterMode();
    $processControl->wait($status);
} else {
    $connection->setSlaveMode();
    sleep(2);

    $packet = new Packet('custom_type');
    $packet->setContent('Hello world');
    $connection->sendPacket($packet);
}
```

#### Single connection with Connection Manager

```php
<?php

declare (ticks = 1);

require_once __DIR__ . '/vendor/autoload.php';

use Thruster\Components\InterProcessCommunication\ConnectionManager;
use Thruster\Components\InterProcessCommunication\Packet;
use Thruster\Components\PosixSignalHandler\SignalHandler;
use Thruster\Wrappers\ProcessControl\ProcessControl;

$signalHandler = new SignalHandler();
$processControl = new ProcessControl();
$connectionManager = new ConnectionManager();
$connectionManager->setSignalHandler($signalHandler);

$connection = $connectionManager->newConnection();
$connection->initialize();

$connectionManager->onPacketReceived(function ($packet) {
    echo 'Slave: ' . $packet->getContent() . PHP_EOL;
});

$pid = $processControl->fork();

if ($pid > 0) {
    $connectionManager->addConnection($connection);
    $processControl->wait($status);
} else {
    $connection->setSlaveMode();
    sleep(2);

    $packet = new Packet();
    $packet->setContent('Hello world');
    $connection->sendPacket($packet);
}
```

#### Single connection with Connection Manager and Packet Handler

```php
<?php

declare (ticks = 1);

require_once __DIR__ . '/vendor/autoload.php';

use Thruster\Components\InterProcessCommunication\PacketHandler;
use Thruster\Components\InterProcessCommunication\ConnectionManager;
use Thruster\Components\InterProcessCommunication\Packet;
use Thruster\Components\PosixSignalHandler\SignalHandler;
use Thruster\Wrappers\ProcessControl\ProcessControl;

$signalHandler = new SignalHandler();
$processControl = new ProcessControl();
$connectionManager = new ConnectionManager();
$packetHandler = new PacketHandler();

$connectionManager->setSignalHandler($signalHandler);

$connection = $connectionManager->newConnection();
$connection->initialize();

$packetHandler->addHandler('custom_type', function($packet) {
    echo "Slave: " . $packet->getContent() . PHP_EOL;
});

$packetHandler->addProvider($connectionManager);

$pid = $processControl->fork();

if ($pid > 0) {
    $connectionManager->addConnection($connection);
    $processControl->wait($status);
} else {
    $connection->setSlaveMode();
    sleep(2);

    $packet = new Packet('custom_type');
    $packet->setContent('Hello world');
    $connection->sendPacket($packet);
}
```

#### Multi connection with Connection Manager

```php
<?php

declare (ticks = 1);

require_once __DIR__ . '/vendor/autoload.php';

use Thruster\Components\InterProcessCommunication\ConnectionManager;
use Thruster\Components\InterProcessCommunication\Packet;
use Thruster\Components\PosixSignalHandler\SignalHandler;
use Thruster\Wrappers\ProcessControl\ProcessControl;

$signalHandler = new SignalHandler();
$processControl = new ProcessControl();
$connectionManager = new ConnectionManager();

$connectionManager->setSignalHandler($signalHandler);

$connectionManager->onPacketReceived(function ($packet) use ($connectionManager) {
    echo 'Slave ' . $packet->getFrom() . ': ' . $packet->getContent() . PHP_EOL;
});

for ($i = 0; $i < 4; $i++) {
    $connection = $connectionManager->newConnection();
    $connection->initialize();

    $pid = $processControl->fork();

    if ($pid > 0) {
        $connectionManager->addConnection($connection);
    } else {
        $connection->setSlaveMode();
        sleep(2);

        $packet = new Packet();
        $packet->setContent('Hello world');
        $connection->sendPacket($packet);
        exit(0);
    }
}

while($i > 0) {
    $processControl->wait($status);
    $i--;
}
```

#### Multi connection with Connection Manager and Package Handler

```php
<?php

declare (ticks = 1);

require_once __DIR__ . '/vendor/autoload.php';

use Thruster\Components\InterProcessCommunication\PacketHandler;
use Thruster\Components\InterProcessCommunication\ConnectionManager;
use Thruster\Components\InterProcessCommunication\Packet;
use Thruster\Components\PosixSignalHandler\SignalHandler;
use Thruster\Wrappers\ProcessControl\ProcessControl;

$signalHandler = new SignalHandler();
$processControl = new ProcessControl();
$connectionManager = new ConnectionManager();
$packetHandler = new PacketHandler();

$connectionManager->setSignalHandler($signalHandler);

$packetHandler->addHandler('custom_type', function($packet) {
    echo 'Slave ' . $packet->getFrom() . ': ' . $packet->getContent() . PHP_EOL;
});

$packetHandler->addProvider($connectionManager);

for ($i = 0; $i < 4; $i++) {
    $connection = $connectionManager->newConnection();
    $connection->initialize();

    $pid = $processControl->fork();

    if ($pid > 0) {
        $connectionManager->addConnection($connection);
    } else {
        $connection->setSlaveMode();
        sleep(2);

        $packet = new Packet('custom_type');
        $packet->setContent('Hello world');
        $connection->sendPacket($packet);
        exit(0);
    }
}

while($i > 0) {
    $processControl->wait($status);
    $i--;
}

```
<script src="http://yandex.st/highlightjs/7.3/highlight.min.js"></script>
<link rel="stylesheet" href="http://yandex.st/highlightjs/7.3/styles/github.min.css">
<script>
  hljs.initHighlightingOnLoad();
</script>
