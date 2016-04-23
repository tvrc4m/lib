<?php


define('TICKET_STATUS_INIT',0); // 未调用
define('TICKET_STATUS_SUCCESS',1); // 出票成功
define('TICKET_STATUS_ERROR',-1); // 出票失败
define('TICKET_STATUS_REFUND',2); // 已退款


define('TICKET_ORDER_SUCCESS', '100'); // 订单预订成功
define('TICKET_ORDERED', '101'); // 订单已经预定过
define('TICKET_ORDER_ERROR', '102'); // 订单预定失败
