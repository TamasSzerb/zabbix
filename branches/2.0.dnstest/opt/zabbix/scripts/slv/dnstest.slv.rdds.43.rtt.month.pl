#!/usr/bin/perl -w
#
# RDDS43 monthly resolution RTT

use lib '/opt/zabbix/scripts';

use DNSTest;
use DNSTestSLV;

my $cfg_key_in = 'dnstest.rdds.43.rtt[{$DNSTEST.TLD}]';
my $cfg_key_out = 'dnstest.slv.rdds.43.rtt.month';

parse_opts();
exit_if_running();

my $config = get_dnstest_config();
set_slv_config($config);

my ($from, $till, $value_ts) = get_month_bounds();

my $interval = $till + 1 - $from;

zapi_connect();

exit_if_lastclock($tld, $cfg_key_out, $value_ts, $interval);

info("from:$from till:$till value_ts:$value_ts");

my $cfg_minonline = zapi_get_macro_rdds_probe_online();
my $cfg_max_value = zapi_get_macro_rdds_rtt();
my $cfg_delay = zapi_get_macro_rdds_delay();

db_connect();

process_slv_monthly($tld, $cfg_key_in, $cfg_key_out, $from, $till, $value_ts, $cfg_delay, \&check_item_value);

exit(SUCCESS);

sub check_item_value
{
    my $value = shift;

    return (is_service_error($value) == SUCCESS or $value > 5 * $cfg_max_value) ? FAIL : SUCCESS;
}
