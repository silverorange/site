create or replace view AdReferrerByPeriodView as

select id as ad,
-- {{{ day
(select count(ad)
from AdReferrer
where AdReferrer.createdate > (LOCALTIMESTAMP - interval 1 day) and AdReferrer.ad = Ad.id
group by ad)
as day,
-- }}}
-- {{{ week
(select count(ad)
from AdReferrer
where AdReferrer.createdate > (LOCALTIMESTAMP - interval 1 week) and AdReferrer.ad = Ad.id
group by ad)
as week,
-- }}}
-- {{{ 2 week
(select count(ad)
from AdReferrer
where AdReferrer.createdate > (LOCALTIMESTAMP - interval 2 week) and AdReferrer.ad = Ad.id
group by ad)
as two_week,
-- }}}
-- {{{ month
(select count(ad)
from AdReferrer
where AdReferrer.createdate > (LOCALTIMESTAMP - interval 1 month) and AdReferrer.ad = Ad.id
group by ad)
as month,
-- }}}
-- {{{ total
(select count(ad)
from AdReferrer
where AdReferrer.ad = Ad.id
group by ad)
as total
-- }}}
from Ad;
