#!/usr/bin/perl -w
#
# DNS NS availability

use lib '/opt/zabbix/scripts';

use DNSTest;
use DNSTestSLV;

my $cfg_key_in = 'dnstest.dns.udp.rtt[{$DNSTEST.TLD},';
my $cfg_key_out = 'dnstest.slv.dns.ns.avail[';

parse_opts();
exit_if_running();

my ($from, $till, $value_ts) = get_minute_bounds();

info("from:$from till:$till value_ts:$value_ts");

my $config = get_dnstest_config();
set_slv_config($config);

zapi_connect();

my $cfg_minonline = zapi_get_macro_dns_probe_online();
my $cfg_max_value = zapi_get_macro_dns_udp_rtt();

db_connect();

process_slv_ns_avail($tld, $cfg_key_in, $cfg_key_out, $from, $till, $value_ts, $cfg_minonline, 49, \&check_item_value);

exit(SUCCESS);

sub check_item_value
{
    my $value = shift;

    return (is_service_error($value) == SUCCESS or $value > 5 * $cfg_max_value) ? FAIL : SUCCESS;
}
