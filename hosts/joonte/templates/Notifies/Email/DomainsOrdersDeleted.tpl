{*
 *  Joonte Billing System
 *  Copyright © 2012 Vitaly Velikodnyy
 *}
{assign var=Theme value="Заказ на домен {$Params.DomainName|default:'$Params.DomainName'}.{$Params.Name|default:'$Params.Name'} удален" scope=global}
Здравствуйте, {$Params.User.Name|default:'$Params.User.Name'}!

Уведомляем Вас о том, что{$Params.StatusDate|date_format:"%d.%m.%Y"} Ваш заказ №{$Params.OrderID|string_format:"%05u"} на регистрацию домена [{$Params.DomainName|default:'$Params.DomainName'}.{$Params.Name|default:'$Params.Name'}] был удален.
Теперь Вы не являетесь владельцем данного доменного имени. Его в любой момент смогут занять другие лица.

{$Params.From.Sign|default:'$Params.From.Sign'}
