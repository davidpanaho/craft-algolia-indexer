<?php

namespace Craft;

use AlgoliaSearch\Client as AlgoliaClient;

class AlgoliaIndexerService extends BaseApplicationComponent
{
    protected $algoliaClient;

    public function getAlgoliaClient() {
        if (is_null($this->algoliaClient)) {
            $this->algoliaClient = new AlgoliaClient(
                craft()->config->get('appId', 'algoliaindexer'),
                craft()->config->get('adminKey', 'algoliaindexer')
            );
        }
        return $this->algoliaClient;
    }

    public function indexUser($id)
    {
        $user = craft()->users->getUserById($id);

        if ($user) {
            $record = $this->transformUser($user);

            $indexName = craft()->config->get('userIndex', 'algoliaindexer');
            $index = $this->getAlgoliaClient()->initIndex($indexName);
            $res = $index->addObject($record);
        }
    }

    public function reIndexUsers()
    {
        $indexName = craft()->config->get('userIndex', 'algoliaindexer');

        $this->clearIndex($indexName);

        // TODO: this could be generalised by following this plugin:
        // https://github.com/joshuabaker/craft-algolia

        // Set user criteria
        $userCriteria = craft()->elements->getCriteria(ElementType::User);
        $userCriteria->status = 'active';
        $userCriteria->groupId = 1;
        $users = $userCriteria->find();

        // Create records array to pass into addRecords method
        $records = [];

        foreach ($users as $key => $user) {
            $record = $this->transformUser($user);
            $records[] = $record;
        }

        $this->addRecords($indexName, $records);
    }

    public function addRecords($indexName, $records)
    {
        $index = $this->getAlgoliaClient()->initIndex($indexName);
        $res = $index->addObjects($records);

        $this->addReplicaIndices($index, $indexName);
    }

    public function clearIndex($indexName)
    {
        $index = $this->getAlgoliaClient()->initIndex($indexName);
        $index->clearIndex();
    }

    public function addReplicaIndices($index, $indexName)
    {
        // TODO: the settings should be put somewhere else. Maybe in config file?
        $replicas = [$indexName . '_firstName_asc'];
        $index->setSettings([
            'replicas' => $replicas,
        ]);

        $client = $this->getAlgoliaClient();

        $indexFirstNameAsc = $client->initIndex($replicas[0]);
        $indexFirstNameAsc->setSettings([
            'ranking' => [
                'asc(firstName)',
                'typo',
                'geo',
                'words',
                'filters',
                'proximity',
                'attribute',
                'exact',
                'custom',
            ],
        ]);
    }

    public function transformUser($user)
    {
        $tags = [];

        foreach ($user->userSpecialities as $key => $value) {
            $tags[] = $value->title;
        }

        $images = [];
        $profileImage = $user->userProfileImage;
        foreach ($profileImage as $image) {
            $images['small'] = $image->getUrl('small');
            $images['medium'] = $image->getUrl('medium');
            $images['large'] = $image->getUrl('large');
            $images['original'] = $image->url;
        }

        $record = [
            'objectID' => $user->id,
            'id' => $user->id,
            'firstName' => $user->firstName,
            'lastName' => $user->lastName,
            'jobTitle' => $user->userJobTitle,
            'phone' => $user->userPhone,
            'email' => $user->email,
            'profileImages' => $images,
            'specialities' => $tags,
        ];

        // If the user doesn't have a bio, give them an empty one
        if ($user->userBio) {
            $bio = $user->userBio->getRawContent();
        } else {
            $bio = '';
        }
        $record['bio'] = $bio;

        return $record;
    }
}
