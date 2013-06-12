create or replace view SuspiciousAccountView as

SELECT accountloginhistory.account,
	min(accountloginhistory.login_date) AS first_suspicious_login,
	max(accountloginhistory.login_date) AS last_suspicious_login,
	count(accountloginhistory.id) AS login_count,
	count(DISTINCT accountloginhistory.user_agent) AS user_agent_count,
	count(DISTINCT accountloginhistory.ip_address) AS ip_address_count
FROM accountloginhistory

/* In the past week there have been more than 5 logins from more than 5 IPs and more than 5 user-agents */
WHERE accountloginhistory.login_date > (now() - '7 days'::interval)
GROUP BY accountloginhistory.account
HAVING count(accountloginhistory.id) > 4
	and count(DISTINCT accountloginhistory.user_agent) > 4
	and count(DISTINCT accountloginhistory.ip_address) > 4
;

