<?php
/**
 *
 *  Joonte Billing System
 *
 *  Copyright © 2012 Vitaly Velikodnyy
 *
 */
 class HostingOrdersActiveMsg extends Message {
     public function __construct(array $params, $toUser) {
         parent::__construct('HostingOrdersActive', $toUser);

         $this->setParams($params);
     }
 }