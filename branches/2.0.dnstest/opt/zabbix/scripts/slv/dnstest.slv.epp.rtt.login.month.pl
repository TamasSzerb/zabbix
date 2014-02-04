#!/usr/bin/perl -w
#
# EPP login-command monthly resolution RTT

use lib '/opt/zabbix/scripts';

use DNSTest;
use DNSTestSLV;

my $cfg_key_in = 'dnstest.epp.rtt[{$DNSTEST.TLD},login]';
my $cfg_key_out = 'dnstest.slv.epp.rtt.login.month';

parse_opts();
exit_if_running();

set_slv_config(get_dnstest_config());

my ($from, $till, $value_ts) = get_month_bounds();

my $interval = $till + 1 - $from;

zapi_connect();

slv_exit(SUCCESS) if (check_lastclock($tld, $cfg_key_out, $value_ts, $interval) != SUCCESS);

info("from:$from till:$till value_ts:$value_ts");

my $cfg_max_value = zapi_get_macro_epp_rtt('login');
my $cfg_delay = zapi_get_macro_epp_delay();

db_connect();

process_slv_monthly($tld, $cfg_key_in, $cfg_key_out, $from, $till, $value_ts, $cfg_delay, \&check_item_value, MIN_LOGIN_ERROR, MAX_LOGIN_ERROR);

slv_exit(SUCCESS);

sub check_item_value
{
    my $value = shift;

    return (is_service_error($value) == SUCCESS or $value > RTT_LIMIT_MULTIPLIER * $cfg_max_value) ? FAIL : SUCCESS;
}
