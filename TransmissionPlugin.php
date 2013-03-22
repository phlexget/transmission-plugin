<?php

namespace Phlexget\TransmissionPlugin;

use Phlexget\Event\Task;
use Phlexget\Plugin\AbstractPlugin;

use Buzz\Browser as Buzz;
use Buzz\Client\Curl;
use Buzz\Listener\BasicAuthListener;

use adanlobato\Transmission\Transmission;

class TransmissionPlugin extends AbstractPlugin
{
    public static function getSubscribedEvents()
    {
        return array(
            'phlexget.prepare' => array('onPhlexgetPrepare', 0),
            'phlexget.output' => array('onPhlexgetOutput', 0),
        );
    }

    public function onPhlexgetPrepare(Task $task)
    {
        $config = $task->getConfig();
        if (!isset($config['transmission'])) {
            return;
        }

        $container = $task->getApplication()->getContainer();
        $container['transmission'] = $container->share(function($container) use ($config){
            $buzz = new Buzz(new Curl());
            if ($config['transmission']['username'] && $config['transmission']['password']) {
                $buzz->addListener(new BasicAuthListener(
                    $config['transmission']['username'],
                    $config['transmission']['password']
                ));
            }

            return new Transmission($buzz);
        });
    }

    public function onPhlexgetOutput(Task $task)
    {
        $config = $task->getConfig();
        if (!isset($config['transmission'])) {
            return;
        }

        $task->getOutput()->writeln('<comment>Transmission Plugin</comment>:');
        $container = $task->getApplication()->getContainer();

        /** @var Transmission $transmission */
        $transmission = $container['transmission'];
        foreach ($task['torrents'] as $torrent) {
            $task->getOutput()->writeln(sprintf(' - Adding <info>%s</info> to Transmission.', $torrent['title']));
            $transmission->torrent()->add($torrent['link']);
        }
    }
}