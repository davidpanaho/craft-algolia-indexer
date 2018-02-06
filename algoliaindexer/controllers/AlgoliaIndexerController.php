<?php

namespace Craft;

class AlgoliaIndexerController extends BaseController
{
    public function actionIndexUsers()
    {
        craft()->algoliaIndexer->reIndexUsers();
        craft()->userSession->setNotice('Users re-indexed');
        $this->redirect('algoliaindexer');
    }
}
