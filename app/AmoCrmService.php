<?php

namespace App;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Filters\EventsFilter;
use AmoCRM\Models\NoteType\CommonNote;
use League\OAuth2\Client\Token\AccessTokenInterface;

class AmoCrmService
{
    public function __construct(
        protected AmoCRMApiClient $apiClient,
        protected AmoCrmTokenStore $tokenStore
    ){
        $this->connectToApi();
    }

    protected function connectToApi(): void
    {
        if (! $this->tokenStore->isToken()) {
            $accessToken = $this->apiClient->getOAuthClient()->getAccessTokenByCode(env('AMO_CODE'));

            $this->tokenStore->saveToken([
                    'accessToken' => $accessToken->getToken(),
                    'refreshToken' => $accessToken->getRefreshToken(),
                    'expires' => $accessToken->getExpires(),
                    'baseDomain' => env('AMO_BASE_DOMAIN')]
            );
        }

        $accessToken = $this->tokenStore->getToken();

        $this->apiClient->setAccessToken($accessToken)
            ->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
            ->onAccessTokenRefresh(
                function (AccessTokenInterface $accessToken, string $baseDomain) {
                    $this->tokenStore->saveToken(
                        [
                            'accessToken' => $accessToken->getToken(),
                            'refreshToken' => $accessToken->getRefreshToken(),
                            'expires' => $accessToken->getExpires(),
                            'baseDomain' => $baseDomain,
                        ]
                    );
                }
            );
    }

    protected function addNoteAfterAddEntity(string $entity, array $data): void
    {
        $user = $this->apiClient->users()->getOne($data[$entity]['add'][0]['responsible_user_id']);

        $message = $user->getName() . ' ' . date('Y:m:d', $data[$entity]['add'][0]['created_at']);

        if (isset($data[$entity]['add'][0]['name'])) {
            $message = $data[$entity]['add'][0]['name'] . ' ' . $message;
        }

        $note = new CommonNote();
        $note->setEntityId($data[$entity]['add'][0]['id']);
        $note->setText($message);
        $note->setCreatedBy(0);

        $this->apiClient->notes($entity)->addOne($note);
    }

    protected function addNoteAfterUpdateEntity(string $entity, array $data): void
    {
        if ( !isset($data[$entity]['update'][0]['custom_fields'])) {
            return;
        }

        $user = $this->apiClient->users()->getOne($data[$entity]['update'][0]['responsible_user_id']);

        $message = $user->getName() . ' ' . date('Y:m:d', $data[$entity]['update'][0]['updated_at']);

        if (isset($data[$entity]['update'][0]['name'])) {
            $message = $data[$entity]['update'][0]['name'] . ' ' . $message;
        }

        // выбираем последние события
        $filter = new EventsFilter();
        $filter->setTypes(['custom_field_value_changed']);
        $filter->setEntity([$entity]);
        $filter->setEntityIds([$data[$entity]['update'][0]['id']]);
        $filter->setLimit(count($data[$entity]['update'][0]['custom_fields']));

        $events = $this->apiClient->events()->get($filter)->toArray();

        // находим названия полей
        foreach ($events as $event) {
            foreach ($data[$entity]['update'][0]['custom_fields'] as $field) {

                if($field['id'] == $event['value_after'][0]['custom_field_value']['field_id']
                    && intval($data[$entity]['update'][0]['updated_at']) <= $event['created_at']
                ) {
                    $message .= "\n" . $field['name'] . ': ' . $event['value_after'][0]['custom_field_value']['text'];

                    break;
                }
            }
        }

        $note = new CommonNote();
        $note->setEntityId($data[$entity]['update'][0]['id']);
        $note->setText($message);
        $note->setCreatedBy(0);

        $this->apiClient->notes($entity)->addOne($note);
    }

    public function addNote(array $data): void
    {
        if (isset($data['leads']['add'])) {
            $this->addNoteAfterAddEntity('leads', $data);
        }

        if (isset($data['leads']['update'])) {
            $this->addNoteAfterUpdateEntity('leads', $data);
        }

        if (isset($data['contacts']['add'])) {
            $this->addNoteAfterAddEntity('contacts', $data);
        }

        if (isset($data['contacts']['update'])) {
            $this->addNoteAfterUpdateEntity('contacts', $data);
        }
    }
}
