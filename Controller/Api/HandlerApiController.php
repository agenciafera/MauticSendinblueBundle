<?php

namespace MauticPlugin\MauticSendinblueBundle\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\DoNotContact;

class HandlerApiController extends CommonController
{
    public function handleAction()
    {
        $response = new JsonResponse();

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
     * Verifica os tipos de bounces dentro do Sendinblue
     * @reference https://apidocs.sendinblue.com/webhooks/#3
     */
    private function handleBounce($hookJson)
    {
        $blockedEvents = ['hard_bounce', 'soft_bounce', 'blocked', 'spam', 'invalid_email', 'deferred', 'unsubscribe'];
        $currentEvent = $hookJson['event'];
        $email = $hookJson['email'];
        
        if (in_array($currentEvent, $blockedEvents)) {
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
        
        if ($lead) {
            $channel = 'email';
            $comments = 'Contato Bounce via plugin Sendinblue';
            $reason = DoNotContact::BOUNCED;
            $entity->addDncForLead($lead, $channel, $comments, $reason);
        }

        return $lead;
    }
}
