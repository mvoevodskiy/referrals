<?php

switch ($modx->event->name) {
    case 'refOnPrepareUpdateAccount':
        $order = $modx->getObject('msOrder', $orderId);
        $propsElem = $referrals->config['orderPropertiesElement'];
        /** @var msOrder $order */
        $properties = $order->get('properties');
        $refProperties = $properties[$propsElem];
        if ($userId !== $order->user_id && $refProperties['useFromAccount'] && $refUser = $modx->getObject('refUser', ['user' => $userId])) {
            $type = $refProperties['useFromAccount']['type'];
            foreach ($order->Products as $good) {
                $ctx = $good->Product->context_key;
            }
            if ($ctx != 'web' && $ctx != $refUser->ctx) {
                $modx->event->returnedValues = $modx->event->returnedValues ?? [];
                $modx->event->returnedValues['delta'] = 0;
            }

        }
        break;
}