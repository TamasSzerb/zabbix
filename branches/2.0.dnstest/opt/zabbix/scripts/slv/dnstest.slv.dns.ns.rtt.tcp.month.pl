#!/usr/bin/perl -w
#
# DNS monthly resolution RTT (TCP)
#
# 1) run through all periods in a month (dnstest.dns.tcp.rtt delay)
# 2) for each period calculate resolution RTT of every NS taking probe status into account
# 3) calculate and save the percentage of successful RTTs

use lib '/opt/zabbix/scripts';

use DNSTest;
use DNSTestSLV;

my $cfg_key_in = 'dnstest.dns.tcp.rtt[{$DNSTEST.TLD},';
my $cfg_key_out = 'dnstest.slv.dns.ns.rtt.tcp.month[';

parse_opts();
exit_if_running();

my $config = get_dnstest_config();
set_slv_config($config);

my ($from, $till, $value_ts) = get_month_bounds();

my $interval = $till + 1 - $from;

zapi_connect();

exit_if_lastclock($tld, $cfg_key_out, $value_ts, $interval);

info("from:$from till:$till value_ts:$value_ts");

my $cfg_minonline = zapi_get_macro_dns_probe_online();
my $cfg_max_value = zapi_get_macro_dns_tcp_rtt();
my $cfg_delay = zapi_get_macro_dns_tcp_delay();

db_connect();

process_slv_ns_monthly($tld, $cfg_key_in, $cfg_key_out, $from, $till, $value_ts, $cfg_delay, \&check_item_value);

exit(SUCCESS);

sub check_item_value
{
    my $value = shift;

    return (is_service_error($value) == SUCCESS or $value > 5 * $cfg_max_value) ? FAIL : SUCCESS;
}
