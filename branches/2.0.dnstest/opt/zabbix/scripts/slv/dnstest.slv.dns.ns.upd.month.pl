#!/usr/bin/perl -w
#
# DNS monthly update time
#
# 1) run through all periods in a month
# 2) for each period calculate the update time of every NS taking probe status into account
# 3) calculate and save the percentage of successful update times

use lib '/opt/zabbix/scripts';

use DNSTest;
use DNSTestSLV;

my $cfg_key_in = 'dnstest.dns.udp.upd[{$DNSTEST.TLD},';
my $cfg_key_out = 'dnstest.slv.dns.ns.upd.month[';

parse_opts();
exit_if_running();

my $config = get_dnstest_config();
set_slv_config($config);

my ($from, $till, $value_ts) = get_month_bounds();

my $interval = $till + 1 - $from;

zapi_connect();

exit_if_lastclock($tld, $cfg_key_out, $value_ts, $interval);

info("from:$from till:$till value_ts:$value_ts");

my $cfg_update_time = zapi_get_macro_dns_update_time();
my $cfg_delay = zapi_get_macro_dns_udp_delay();

db_connect();

process_slv_ns_monthly($tld, $cfg_key_in, $cfg_key_out, $from, $till, $value_ts, $cfg_delay, \&check_item_value);

slv_exit(SUCCESS);

sub check_item_value
{
    my $value = shift;

    return (is_service_error($value) == SUCCESS or $value > $cfg_update_time) ? FAIL : SUCCESS;
}
