<?php

namespace Craft;

class AlgoliaIndexerPlugin extends BasePlugin
{
    public function getName()
    {
         return 'Algolia Indexer';
    }

    public function getVersion()
    {
        return '1.0.0';
    }

    public function getDeveloper()
    {
        return 'David Panaho';
    }

    public function getDeveloperUrl()
    {
        return 'https://www.davidpanaho.com';
    }

    public function init()
    {
        require_once __DIR__.'/vendor/autoload.php';

        if (craft()->config->get('autoIndex', 'algoliaindexer')) {
            craft()->on('users.onSaveUser', function (Event $event) {
                $user = $event->params['user'];
                craft()->algoliaIndexer->indexUser($user->id);
            });
        }
    }

    public function hasCpSection()
    {
        return true;
    }
}
