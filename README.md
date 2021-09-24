# HappinessThrough 
***
> 融创幸福通接口
***
#使用
```php
<?php
$happinessPullDeal = new HappinessPullDeal([
    'uri' => '',
    'reqsrcsys' => '',
    'reqtarsys' => '',
    'userName' => '',
    'password' => ''
]);
$idCard = '';
$pcode = '';
$happinessPullDeal->getDealImportDatas($idCard,$pcode);
return [[1],[2]];//多个成交数据
```

