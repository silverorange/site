create or replace view SuspiciousAccountView as
	select
		account,
		lag(login_date, 2, null) over AccountWindow as last_suspicious_login,
		case
			when lag(login_date, 2, null) over AccountWindow - lead(login_date, 2, null) over AccountWindow < interval '1 hour' then true
			else false
		end as too_many_logins,
		case
			when         lag(ip_address, 2, null) over AccountWindow = ip_address
				and  lag(ip_address, 1, null) over AccountWindow = ip_address
				and lead(ip_address, 1, null) over AccountWindow = ip_address
				and lead(ip_address, 2, null) over AccountWindow = ip_address then false
			else true
		end as ip_address_distinct,
		case
			when         lag(user_agent, 2, null) over AccountWindow = user_agent
				and  lag(user_agent, 1, null) over AccountWindow = user_agent
				and lead(user_agent, 1, null) over AccountWindow = user_agent
				and lead(user_agent, 2, null) over AccountWindow = user_agent then false
			else true
		end as user_agent_distinct
	from AccountLoginHistory
	where login_date > now() - interval '7 days'
	window AccountWindow as (
		partition by account
		order by login_date desc
		rows between unbounded preceding and unbounded following
	)
	order by account, login_date desc;
