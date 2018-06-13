<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Sendinblue',
    'description' => 'API de comunicação entre a ferramenta Sendinblue e Mautic',
    'version'     => '1.0',
    'author'      => 'Agência Fera',
    'routes' => [
        'api' => [
            'mautic_api_sendinblue' => [
                'path'       => '/sendinblue/handler',
                'controller' => 'MauticSendinblueBundle:Api\HandlerApi:handle',
                'method'     => 'POST',
            ],
        ],
    ],
];
