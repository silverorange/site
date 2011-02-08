create or replace view SuspiciousAccountView as
	select
		account,
		count(1) as login_count,
		count(distinct user_agent) as user_agent_count,
		count(distinct ip_address) as ip_address_count
	from AccountLoginHistory
	where login_date > now() - interval '1 hour'
	group by account
	having count(1) > 5 and
		(count(distinct user_agent) > 1 or count(distinct ip_address) > 1);
