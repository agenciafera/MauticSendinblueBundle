<?php

namespace MauticPlugin\MauticSendinblueBundle\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\DoNotContact;

class HandlerApiController extends CommonController
{
    public function handleAction()
    {
        $response = new JsonResponse();
        // $this->get('monolog.logger.mautic')->error('Request Params', $dataRequest);
        // $this->get('monolog.logger.mautic')->error('JSON Recebidos', $dataRequestJson);

        try {
            // request Json
            $dataRequestJson = json_decode(file_get_contents('php://input'), true);
            $lead = $this->handleBounce($dataRequestJson);
            $response->setData(['success' => true, 'lead' => $lead->getId()]);
        } catch (\Exception $e) {
            $response->setData(['success' => false, 'message'=> $e->getMessage()]);
        }

        // response
        return $response;
    }

    /**
     * @reference https://apidocs.sendinblue.com/webhooks/#3
     */
    private function handleBounce($hookJson)
    {
        /*
        // padrao de dados
        {
            "event":"delivered",
            "email":"example@example.net",
            "id":1,
            "date":"2013-06-16 10:08:14",
            "message-id":"<201306160953.85395191262@msgid.domain>",
            "tag":"defined-tag",
            "X-Mailin-custom":"defined-custom-value",
            "reason":"Reason",
            "link":"http://example.net"
        }
        */

        $blockedEvents = ['hard_bounce', 'soft_bounce', 'blocked', 'spam', 'invalid_email', 'deferred', 'unsubscribe'];
        $currentEvent = $hookJson['event'];
        if (in_array($currentEvent, $blockedEvents)) {
            $email = $hookJson['email'];
            return $this->dncByEmail($email);
        }
    }

    /**
     * Adiciona um email a lista de emails DND = Do not contact
     * Todos os contatos serão marcados como Bounceds
     */
    private function dncByEmail($email)
    {
        $entity = $this->getModel('lead.lead');
        $uniqueLeadFieldData = ['email' => $email];

        $leads = $this->get('doctrine.orm.entity_manager')->getRepository('MauticLeadBundle:Lead')->getLeadsByUniqueFields($uniqueLeadFieldData, null, 1);
        $lead = ($leads) ? $leads[0] : null;
        $channel = 'email';
        $comments = 'Contato Bounce via plugin Sendinblue';
        $reason = DoNotContact::BOUNCED;
        $entity->addDncForLead($lead, $channel, $comments, $reason);

        return $lead;
    }
}
