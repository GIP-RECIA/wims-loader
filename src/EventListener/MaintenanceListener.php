<?php
/**
 * Copyright © 2024 GIP-RECIA (https://www.recia.fr/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Twig\Environment;

class MaintenanceListener
{
    private $twig;
    private $maintenanceMode;
    private $maintenanceMessage;

    public function __construct(Environment $twig, ?string $maintenanceMode, ?string $maintenanceMessage)
    {
        $this->twig = $twig;
        $this->maintenanceMode = $maintenanceMode === 'true';
        $this->maintenanceMessage = $maintenanceMessage;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        // Ne pas appliquer dans la console ou sur les sous-requêtes
        if (!$event->isMainRequest()) {
            return;
        }

        // Si le mode maintenance est activé, afficher la page de maintenance
        if ($this->maintenanceMode) {
            $response = new Response(
                $this->twig->render('web/maintenance.html.twig', [
                    'maintenance_message' => $this->maintenanceMessage
                ]),
                Response::HTTP_SERVICE_UNAVAILABLE
            );

            // Empêcher d'autres listeners de manipuler cette requête
            $event->setResponse($response);
        }
    }
}