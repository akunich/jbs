<?php
/**
 *
 *  Joonte Billing System
 *
 *  Copyright © 2012 Vitaly Velikodnyy
 *
 */
 class ExtraIPOrdersActiveMsg extends Message {
     public function __construct(array $params, $toUser) {
         parent::__construct('ExtraIPOrdersActive', $toUser);

         $this->setParams($params);
     }
 }