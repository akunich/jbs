<?php
/**
 *
 *  Joonte Billing System
 *
 *  Copyright © 2012 Vitaly Velikodnyy
 *
 */
 class HostingOrdersSuspendedMsg extends Message {
     public function __construct(array $params, $toUser) {
         parent::__construct('HostingOrdersSuspended', $toUser);

         $this->setParams($params);
     }
 }