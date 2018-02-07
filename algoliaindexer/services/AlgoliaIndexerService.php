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
            ];

            // If the user doesn't have a bio, give them an empty one
            if ($user->userBio) {
                $bio = $user->userBio->getRawContent();
            } else {
                $bio = '';
            }
            $record['bio'] = $bio;

            $records[] = $record;
        }

        $this->addRecords($indexName, $records);
    }

    public function addRecords($indexName, $records)
    {
        $index = $this->getAlgoliaClient()->initIndex($indexName);
        $res = $index->addObjects($records);
    }

    public function clearIndex($indexName)
    {
        $index = $this->getAlgoliaClient()->initIndex($indexName);
        $index->clearIndex();
    }
}
