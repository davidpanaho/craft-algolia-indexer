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
        
    }

    public function hasCpSection()
    {
        return true;
    }
}
