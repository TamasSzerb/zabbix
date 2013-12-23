#!/usr/bin/perl -w
#
# DNS availability

use lib '/opt/zabbix/scripts';

use DNSTest;
use DNSTestSLV;

my $cfg_key_in = 'dnstest.dns.udp[{$DNSTEST.TLD}]';
my $cfg_key_out = 'dnstest.slv.dns.avail';

parse_opts();
exit_if_running();

my $config = get_dnstest_config();
set_slv_config($config);

zapi_connect();

my ($from, $till, $value_ts) = get_minute_bounds();

info("from:$from till:$till value_ts:$value_ts");

my $cfg_minonline = zapi_get_macro_dns_probe_online();
my $cfg_minns = zapi_get_macro_minns();

db_connect();

my $probes_ref = get_online_probes($from, $till, undef);
my $count = scalar(@$probes_ref);
if ($count < $cfg_minonline)
{
    info("success ($count probes are online, min - $cfg_minonline)");
    send_value($tld, $cfg_key_out, $value_ts, UP);
    exit(SUCCESS);
}

my $hostids_ref = probes2tldhostids($tld, $probes_ref);

my $items_ref = zapi_get_items_by_hostids($hostids_ref, $cfg_key_in, 1); # complete key

my $values_ref = get_item_values($items_ref, $from, $till);
$count = scalar(keys(%$values_ref));
if ($count < $cfg_minonline)
{
    info("success ($count online probes have results, min - $cfg_minonline)");
    send_value($tld, $cfg_key_out, $value_ts, UP);
    exit(SUCCESS);
}

my $success_probes = 0;
foreach my $itemid (keys(%$values_ref))
{
    my $probe_result = check_item_values($values_ref->{$itemid});

    $success_probes++ if (SUCCESS == $probe_result);

    my $hostid = -1;
    foreach (@$items_ref)
    {
	if ($_->{'itemid'} == $itemid)
	{
	    $hostid = $_->{'hostid'};
	}
    }

    info("i:$itemid ", "(h:$hostid): ", (SUCCESS == $probe_result ? "success" : "fail"), " (values: ", join(', ', @{$values_ref->{$itemid}}), ")");
}

my $test_result = DOWN;
$test_result = UP if ($success_probes * 100 / scalar(@$items_ref) > 49);    

info($test_result == UP ? "success" : "fail");
send_value($tld, $cfg_key_out, $value_ts, $test_result);

exit(SUCCESS);

# SUCCESS - no values or at least one successful value
# FAIL - all values unsuccessful
sub check_item_values
{
    my $values_ref = shift;

    return SUCCESS if (scalar(@$values_ref) == 0);

    foreach (@$values_ref)
    {
	return SUCCESS if ($_ >= $cfg_minns);
    }

    return FAIL;
}
