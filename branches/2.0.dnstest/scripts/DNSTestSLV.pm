package DNSTestSLV;

use strict;
use warnings;
use DBI;
use IO::CaptureOutput qw/capture_exec/;
use Getopt::Long;
use Exporter qw(import);
use DateTime;
use Zabbix;

use constant SUCCESS => 0;
use constant FAIL => 1;
use constant UP => 1;
use constant DOWN => 0;

use constant MAX_SERVICE_ERROR => -200; # -200, -201 ...
use constant RDDS_SUCCESS => 2; # results of input items: 0 - RDDS down, 1 - only RDDS43 up, 2 - both RDDS43 and RDDS80 up

use constant TRIGGER_SEVERITY_NOT_CLASSIFIED => 0;
use constant TRIGGER_VALUE_CHANGED_YES => 1;
use constant API_OUTPUT_REFER => 'refer';
use constant TRIGGER_VALUE_TRUE => 1;

our ($zabbix, $result, $dbh, $tld);

our %OPTS; # command-line options

our @EXPORT = qw($zabbix $result $dbh $tld %OPTS
		SUCCESS FAIL UP DOWN RDDS_SUCCESS
		zapi_connect zapi_get_macro_minns zapi_get_macro_dns_probe_online
		zapi_get_macro_rdds_probe_online zapi_get_macro_dns_rollweek_sla zapi_get_macro_rdds_rollweek_sla
		zapi_get_macro_dns_udp_rtt zapi_get_macro_dns_tcp_rtt zapi_get_macro_rdds_rtt
		rtt zapi_get_macro_dns_udp_delay zapi_get_macro_dns_tcp_delay zapi_get_macro_rdds_delay
		zapi_get_macro_dns_update_time zapi_get_macro_rdds_update_time zapi_get_items_by_hostids
		db_connect db_select
		set_slv_config get_minute_bounds get_rdds_bounds get_rollweek_bounds get_month_bounds
		minutes_last_month get_probes get_online_probes probes2tldhostids send_value get_ns_from_key
		is_service_error process_slv_ns_monthly process_slv_ns_avail process_slv_monthly get_item_values
		exit_if_lastclock get_down_count
		dbg info warn fail exit_if_running trim parse_opts);

my $probe_group_name = 'Probes';
my $probe_key_manual = 'probe.status[manual]';
my $probe_key_automatic = 'probe.status[automatic,%]'; # match all in SQL

# configuration, set in set_slv_config()
my $config = undef;

my $avail_shift_back = 2; # minutes
my $rollweek_shift_back = 3; # minutes

sub zapi_connect
{
    $zabbix = Zabbix->new({'url' => $config->{'zapi'}->{'url'}, 'user' => $config->{'zapi'}->{'user'}, 'password' => $config->{'zapi'}->{'password'}});
}

sub zapi_get_macro_minns
{
    return get_macro('{$DNSTEST.DNS.AVAIL.MINNS}');
}

sub zapi_get_macro_dns_probe_online
{
    return get_macro('{$DNSTEST.DNS.PROBE.ONLINE}');
}

sub zapi_get_macro_rdds_probe_online
{
    return get_macro('{$DNSTEST.RDDS.PROBE.ONLINE}');
}

sub zapi_get_macro_dns_rollweek_sla
{
    return get_macro('{$DNSTEST.DNS.ROLLWEEK.SLA}');
}

sub zapi_get_macro_rdds_rollweek_sla
{
    return get_macro('{$DNSTEST.RDDS.ROLLWEEK.SLA}');
}

sub zapi_get_macro_dns_udp_rtt
{
    return get_macro('{$DNSTEST.DNS.UDP.RTT}');
}

sub zapi_get_macro_dns_tcp_rtt
{
    return get_macro('{$DNSTEST.DNS.TCP.RTT}');
}

sub zapi_get_macro_rdds_rtt
{
    return get_macro('{$DNSTEST.RDDS.RTT}');
}

sub zapi_get_macro_dns_udp_delay
{
    return get_macro('{$DNSTEST.DNS.UDP.DELAY}');
}

sub zapi_get_macro_dns_tcp_delay
{
    return get_macro('{$DNSTEST.DNS.TCP.DELAY}');
}

sub zapi_get_macro_rdds_delay
{
    return get_macro('{$DNSTEST.RDDS.DELAY}');
}

sub zapi_get_macro_dns_update_time
{
    return get_macro('{$DNSTEST.DNS.UPDATE.TIME}');
}

sub zapi_get_macro_rdds_update_time
{
    return get_macro('{$DNSTEST.RDDS.UPDATE.TIME}');
}

sub zapi_get_items_by_hostids
{
    my $hostids_ref = shift;
    my $cfg_key = shift;
    my $complete = shift;

    dbg("hostids: ", join(', ', @$hostids_ref));

    my $result;
    if ($complete)
    {
	$result = $zabbix->get('item', {hostids => $hostids_ref, output => 'extend', filter => {key_ => $cfg_key}});
    }
    else
    {
	$result = $zabbix->get('item', {hostids => $hostids_ref, output => 'extend', startSearch => 1, search => {key_ => $cfg_key}});
    }

    my @items;
    if ('ARRAY' eq ref($result))
    {
	push(@items, $_) foreach (@$result);
    }
    elsif (defined($result->{'itemid'}))
    {
	push(@items, $result);
    }
    else
    {
	if ($complete)
	{
	    fail("no input items ($cfg_key)");
	}
	else
	{
	    fail("no input items ($cfg_key*)");
	}
    }

    return \@items;
}

sub db_connect
{
    $dbh = DBI->connect('DBI:mysql:'.$config->{'db'}->{'name'}.':'.$config->{'db'}->{'host'},
			$config->{'db'}->{'user'},
			$config->{'db'}->{'password'});
}

sub db_select
{
    my $query = shift;

    dbg($query);

    my $res = $dbh->prepare($query)
	or fail("cannot prepare $query: $dbh->errstr");

    my $rv = $res->execute()
	or fail("cannot execute the query: $res->errstr");

    return $res;
}

sub set_slv_config
{
    $config = shift;
}

# Get bounds of the previous minute shifted $avail_shift_back minutes back.
sub get_minute_bounds
{
    my $dt = DateTime->now;

    $dt->truncate(to => 'minute');
    $dt->subtract(minutes => $avail_shift_back);
    my $till = $dt->epoch - 1;

    $dt->subtract(minutes => 1);
    my $from = $dt->epoch;

    return ($from, $till, $till - 29);
}

# Get bounds of the previous rdds test period shifted $avail_shift_back minutes back.
sub get_rdds_bounds
{
    my $interval = shift;

    my $dt = DateTime->now;

    $dt->truncate(to => 'minute');
    $dt->subtract(minutes => $avail_shift_back);
    my $till = $dt->epoch - 1;

    $dt->subtract(seconds => $interval);
    my $from = $dt->epoch;

    return ($from, $till, $till - 29);
}

# Get bounds of the previous week shifted $rollweek_shift_back minutes back.
sub get_rollweek_bounds
{
    my $dt = DateTime->now;

    $dt->truncate(to => 'minute');
    $dt->subtract(minutes => $rollweek_shift_back);
    my $till = $dt->epoch - 1;

    $dt->subtract(weeks => 1);
    my $from = $dt->epoch;

    return ($from, $till, $till - 29);
}

# Get bounds of previous month.
sub get_month_bounds
{
    my $dt = DateTime->now;

    $dt->truncate(to => 'month');
    my $till = $dt->epoch - 1;

    $dt->subtract(months => 1);
    my $from = $dt->epoch;

    return ($from, $till, $till - 29);
}

sub minutes_last_month
{
    my $dt = DateTime->now;

    $dt->truncate(to => 'month');
    my $till = $dt->epoch;

    $dt->subtract(months => 1);
    my $from = $dt->epoch;

    return ($till - $from) / 60;
}

# Returns a reference to hash probes (host name => hostid).
sub get_probes
{
    my $res = db_select(
	"select h.host,h.hostid" .
	" from hosts h, hosts_groups hg, groups g" .
	" where h.hostid=hg.hostid" .
		" and hg.groupid=g.groupid" .
		" and g.name='$probe_group_name'");

    my (%result, @row);
    while (@row = $res->fetchrow_array)
    {
	$result{$row[0]} = $row[1];
    }

    return \%result;
}

# Returns a reference to an array of probe names which are online from/till. The algorithm goes like this:
#
# for each manual probe status item
#   get values between $from and $till
#   if there is something
#     if there is at least one DOWN
#       add to the list
#       break
#   else
#     get the latest value before $from
#     if it is DOWN
#       add to the list
# if we did not add it to the list
#   do the same loop for automatic probe status item
#
# You must be connected to the database before calling this function.
sub get_online_probes
{
    my $from = shift;
    my $till = shift;
    my $all_probes_ref = shift;

    $all_probes_ref = get_probes() unless ($all_probes_ref);

    my (@result, @row, $sql, $host, $hostid, $res, $probe_down, $no_values);
    foreach my $host (keys(%$all_probes_ref))
    {
	$hostid = $all_probes_ref->{$host};

	$res = db_select(
	    "select h.value" .
	    " from history_uint h,items i" .
	    " where h.itemid=i.itemid" .
	    	" and i.key_='$probe_key_manual'" .
	    	" and i.hostid=$hostid" .
	    	" and h.clock between $from and $till");

	$probe_down = 0;
	$no_values = 1;
	while (@row = $res->fetchrow_array)
	{
	    $no_values = 0;

	    if ($row[0] == DOWN)
	    {
		$probe_down = 1;
		dbg("  $host ($hostid) down (manual: between $from and $till)");
		last;
	    }
	}

	next if ($probe_down == 1);

	if ($no_values == 1)
	{
	    # We did not get any values between $from and $till, consider the latest value.

	    $res = db_select(
		"select h.value" .
		" from history_uint h,items i" .
		" where h.itemid=i.itemid" .
			" and i.key_='$probe_key_manual'" .
			" and i.hostid=$hostid" .
			" and h.clock<$from" .
		" order by clock desc" .
		" limit 1");

	    if (@row = $res->fetchrow_array)
	    {
		if ($row[0] == DOWN)
		{
		    dbg("  $host ($hostid) down (manual: latest)");
		    next;
		}
	    }
	}

	dbg("  $host ($hostid) up (manual)");

	# Probe is considered manually up, check automatic status.

	$res = db_select(
	    "select h.value" .
	    " from history_uint h,items i" .
	    " where h.itemid=i.itemid" .
	    	" and i.key_ like '$probe_key_automatic'" .
	    	" and i.hostid=$hostid" .
	    	" and h.clock between $from and $till");

	$probe_down = 0;
        $no_values = 1;
	while (@row = $res->fetchrow_array)
        {   
            $no_values = 0;

            if ($row[0] == DOWN)
            {   
		dbg("  $host ($hostid) down (automatic: between $from and $till)");
                $probe_down = 1;
                last;
            }
        }	

	next if ($probe_down == 1);

	if ($no_values == 1)
        {
	    # We did not get any values between $from and $till, consider the latest value.

	    $res = db_select(
                "select h.value" .
                " from history_uint h,items i" .
                " where h.itemid=i.itemid" .
                        " and i.key_ like '$probe_key_automatic'" .
                        " and i.hostid=$hostid" .
                        " and h.clock<$from" .
                " order by clock desc" .
                " limit 1");

	    if (@row = $res->fetchrow_array)
	    {
		if ($row[0] == DOWN)
		{
		    dbg("  $host ($hostid) down (automatic: latest)");
		    next;
		}
	    }
	}	    

	push(@result, $host);
    }

    return \@result;
}

# Translate probe names to hostids of appropriate tld hosts.
#
# E. g., we have hosts (name/id):
#   "Probe2"		1
#   "Probe12"		2
#   "ORG Probe2"	100
#   "ORG Probe12"	101
# calling
#   probes2tldhostids("org", ("Probe2", "Probe12"))
# will return
#  (100, 101)
sub probes2tldhostids
{
    my $tld = shift;
    my $probes_ref = shift;

    my @result;

    my $hosts_str = "";
    foreach (@$probes_ref)
    {
	$hosts_str .= " or " if ("" ne $hosts_str);
	$hosts_str .= "host='$tld $_'";
    }

    if ($hosts_str ne "")
    {
	my $res = db_select("select hostid from hosts where $hosts_str");

	my @row;
	push(@result, $row[0]) while (@row = $res->fetchrow_array);
    }

    return \@result;
}

# <hostname> <key> <timestamp> <value>
sub send_value
{
    my $hostname = shift;
    my $key = shift;
    my $timestamp = shift;
    my $value = shift;

    return if (defined($OPTS{test}));

    fail('zabbix sender ('.$config->{'slv'}->{'sender'}.') not found') unless (defined($config->{'slv'}->{'sender'})
									       and -x $config->{'slv'}->{'sender'});

    my @cmd = ("echo \\\"$hostname\\\" \\\"$key\\\" \\\"$timestamp\\\" \\\"$value\\\" | ".
	       $config->{'slv'}->{'sender'}." -T -z ".$config->{'slv'}->{'zserver'}." -i -");

    my ($stdout, $stderr) = capture_exec(@cmd);

    my @lines = split("\n", $stdout);
    info($_) foreach (@lines);
    @lines = split("\n", $stderr);
    info($_) foreach (@lines);
}

# Get name server details (name, IP) from item key.
#
# E. g.:
#
# dnstest.dns.udp.rtt[{$DNSTEST.TLD},i.ns.se.,194.146.106.22] -> "i.ns.se.,194.146.106.22"
# dnstest.slv.dns.avail[i.ns.se.,194.146.106.22] -> "i.ns.se.,194.146.106.22"
sub get_ns_from_key
{
    my $result = shift;

    $result =~ s/^[^\[]+\[([^\]]+)]$/$1/;

    my $got_params = 0;
    my $pos = length($result);

    while ($pos > 0 and $got_params < 2)
    {
        $pos--;
        my $char = substr($result, $pos, 1);
        $got_params++ if ($char eq ',')
    }

    $pos == 0 ? $got_params++ : $pos++;

    return "" unless ($got_params == 2);

    return substr($result, $pos);
}

sub is_service_error
{
    my $error = shift;

    return SUCCESS if ($error <= MAX_SERVICE_ERROR);

    return FAIL;
}

sub process_slv_ns_monthly
{
    my $tld = shift;
    my $cfg_key_in = shift;      # part of input key, e. g. 'dnstest.dns.udp.upd[{$DNSTEST.TLD},'
    my $cfg_key_out = shift;     # part of output key, e. g. 'dnstest.slv.dns.ns.upd['
    my $from = shift;            # start of SLV period
    my $till = shift;            # end of SLV period
    my $value_ts = shift;        # value timestamp
    my $cfg_interval = shift;    # input values interval
    my $check_value_ref = shift; # a pointer to subroutine to check if the value was successful

    # first we need to get the list of name servers
    my $nss_ref = get_nss($tld, $cfg_key_out);

    # %successful_values is a hash of name server as key and its number of successful results as a value. Name server is
    # represented by a string consisting of name and IP separated by comma. Each successful result means the IP was UP at
    # certain period. E. g.:
    #
    # 'g.ns.se.,2001:6b0:e:3::1' => 150,
    # 'b.ns.se.,192.36.133.107' => 200,
    # ...
    my %successful_values;
    foreach my $ns (@$nss_ref)
    {
	$successful_values{$ns} = 0;
    }

    my $probes_ref = get_probes();

    my $all_ns_items_ref = get_all_ns_items($nss_ref, $cfg_key_in);

    my $cur_from = $from;
    my ($interval, $cur_till);
    my $total_iterations = 0;
    while ($cur_from < $till)
    {    
	$total_iterations++;

	# We treat missing values as successful. Also we treat values received during probe OFFLINE as successful.
	# So we set all the possible values to SUCCESS and then substract number of failed results later.
	foreach my $hostid (keys(%$all_ns_items_ref))
	{
	    foreach my $itemid (keys(%{$all_ns_items_ref->{$hostid}}))
	    {
		my $ns = $all_ns_items_ref->{$hostid}{$itemid};

		$successful_values{$ns}++;
	    }
	}

	$interval = ($cur_from + $cfg_interval > $till ? $till - $cur_from : $cfg_interval);
	$cur_till = $cur_from + $interval;
	$cur_till-- unless ($cur_till == $till); # SQL BETWEEN includes upper bound

	my $online_probes_ref = get_online_probes($cur_from, $cur_till, $probes_ref);

	info("from:$cur_from till:$cur_till diff:", $cur_till - $cur_from, " online:", scalar(@$online_probes_ref));

	my $hostids_ref = probes2tldhostids($tld, $online_probes_ref);

	my $items_ref = get_online_items($hostids_ref, $all_ns_items_ref);

	my $values_ref = get_ns_values($items_ref, $cur_from, $cur_till, $all_ns_items_ref);

	foreach my $ns (keys(%$values_ref))
	{
	    my $item_values_ref = $values_ref->{$ns};

	    foreach (@$item_values_ref)
	    {
		$successful_values{$ns}-- if ($check_value_ref->($_) != SUCCESS);
	    }
	}

	$cur_from += $interval;
    }

    my $values_per_ns = $total_iterations * scalar(keys(%$all_ns_items_ref));
    foreach my $ns (keys(%successful_values))
    {
	my $key_out = $cfg_key_out . $ns . ']';
	my $perc = sprintf("%.0f", $successful_values{$ns} * 100 / $values_per_ns);

	info("$ns: $perc% successful values (", $successful_values{$ns}, " out of $values_per_ns)");
	send_value($tld, $key_out, $value_ts, $perc);
    }
}

sub process_slv_monthly
{
    my $tld = shift;
    my $cfg_key_in = shift;      # e. g. 'dnstest.rdds.43.rtt[{$DNSTEST.TLD}]'
    my $cfg_key_out = shift;     # e. g. 'dnstest.slv.rdds.43.rtt'
    my $from = shift;            # start of SLV period
    my $till = shift;            # end of SLV period
    my $value_ts = shift;        # value timestamp
    my $cfg_interval = shift;    # input values interval
    my $check_value_ref = shift; # a pointer to subroutine to check if the value was successful

    my $probes_ref = get_probes();

    my $all_items_ref = get_all_items($cfg_key_in);

    my $cur_from = $from;
    my ($interval, $cur_till);
    my $total_iterations = 0;
    my $successful_values = 0;

    while ($cur_from < $till)
    {    
	$total_iterations++;

	# We treat missing values as successful. Also we treat values received during probe OFFLINE as successful.
	# So we set all the possible values to SUCCESS and then substract number of failed results later.
	$successful_values++ foreach (keys(%$all_items_ref));

	$interval = ($cur_from + $cfg_interval > $till ? $till - $cur_from : $cfg_interval);
	$cur_till = $cur_from + $interval;
	$cur_till-- unless ($cur_till == $till); # SQL BETWEEN includes upper bound

	my $online_probes_ref = get_online_probes($cur_from, $cur_till, $probes_ref);

	info("from:$cur_from till:$cur_till diff:", $cur_till - $cur_from, " online:", scalar(@$online_probes_ref));

	my $hostids_ref = probes2tldhostids($tld, $online_probes_ref);

	my $online_items_ref = get_online_items($hostids_ref, $all_items_ref);

	my $values_ref = get_values($online_items_ref, $cur_from, $cur_till);

	foreach my $value (@$values_ref)
	{
	    $successful_values-- if ($check_value_ref->($value) != SUCCESS);
	}

	$cur_from += $interval;
    }

    my $total_values = $total_iterations * scalar(keys(%$all_items_ref));
    my $perc = sprintf("%.0f", $successful_values * 100 / $total_values);

    info("$perc% successful values ($successful_values out of $total_values)");
    send_value($tld, $cfg_key_out, $value_ts, $perc);
}

sub process_slv_ns_avail
{
    my $tld = shift;
    my $cfg_key_in = shift;
    my $cfg_key_out = shift;
    my $from = shift;
    my $till = shift;
    my $value_ts = shift;
    my $cfg_minonline = shift;
    my $max_fail_perc = shift;
    my $check_value_ref = shift;

    my $nss_ref = get_nss($tld, $cfg_key_out);

    my @out_keys;
    push(@out_keys, $cfg_key_out . $_ . ']') foreach (@$nss_ref);

    my $online_probes_ref = get_online_probes($from, $till, undef);
    my $count = scalar(@$online_probes_ref);
    if ($count < $cfg_minonline)
    {
	info("success ($count probes are online, min - $cfg_minonline)");
	send_value($tld, $_, $value_ts, UP) foreach (@out_keys);
	exit SUCCESS;
    }

    my $all_ns_items_ref = get_all_ns_items($nss_ref, $cfg_key_in);

    my $hostids_ref = probes2tldhostids($tld, $online_probes_ref);

    my $online_items_ref = get_online_items($hostids_ref, $all_ns_items_ref);

    my $values_ref = get_ns_values($online_items_ref, $from, $till, $all_ns_items_ref);

    warn("no values found in the database") if (scalar(keys(%$values_ref)) == 0);

    foreach my $ns (keys(%$values_ref))
    {
	my $item_values_ref = $values_ref->{$ns};
	my $count = scalar(@$item_values_ref);
	my $out_key = $cfg_key_out . $ns . ']';

	if ($count < $cfg_minonline)
	{
	    info("$ns success ($count online probes have results, min - $cfg_minonline)");
	    send_value($tld, $out_key, $value_ts, UP);
	    next;
	}

	my $success_results = 0;
	foreach (@$item_values_ref)
	{
	    info("  ", $_);
	    $success_results++ if ($check_value_ref->($_) == SUCCESS);
	}

	my $success_perc = $success_results * 100 / $count;
	my $test_result = $success_perc > $max_fail_perc ? UP : DOWN;
	info("$out_key: ", $test_result == UP ? "success" : "fail", " (", sprintf("%.2f", $success_perc), "% success)");
	send_value($tld, $out_key, $value_ts, $test_result);
    }
}

# organize values from all hosts grouped by itemid and return itemid->values hash
#
# E. g.:
#
# '10010' => [
#          205
#    ];
# '10011' => [
#          -102
#          304
#    ];
sub get_item_values
{
    my $items_ref = shift;
    my $from = shift;
    my $till = shift;

    my %result;

    if (0 < scalar(@$items_ref))
    {
	my $items_str = "";
	foreach (@$items_ref)
	{
	    $items_str .= "," if ("" ne $items_str);
	    $items_str .= $_->{'itemid'};
	}

	my $res = db_select("select itemid,value from history_uint where itemid in ($items_str) and clock between $from and $till");

	while (my @row = $res->fetchrow_array)
	{
	    my $itemid = $row[0];
	    my $value = $row[1];

	    if (exists($result{$itemid}))
	    {
		$result{$itemid} = [@{$result{$itemid}}, $value];
	    }
	    else
	    {
		$result{$itemid} = [$value];
	    }
	}
    }

    return \%result;    
}

sub exit_if_lastclock
{
    my $tld = shift;
    my $cfg_key_out = shift;
    my $value_ts = shift;
    my $interval = shift;

    return if (defined($OPTS{test}));

    my ($result, $lastclock);

    if ("[" eq substr($cfg_key_out, -1))
    {
	$result = $zabbix->get('item', {host => $tld, output => ['lastclock'], sartSearch => 1, search => {key_ => $cfg_key_out}});

	if ('ARRAY' eq ref($result))
	{
	    $lastclock = $result->[0]->{'lastclock'};
	}
	elsif (defined($result->{'lastclock'}))
	{
	    $lastclock = $result->{'lastclock'};
	}
	else
	{
	    fail("cannot find items at host $tld ($cfg_key_out)");
	}
    }
    else
    {
	$result = $zabbix->get('item', {host => $tld, output => ['lastclock'], filter => {key_ => $cfg_key_out}});
	$lastclock = $result->{'lastclock'} || 0;
    }

    if ($lastclock + $interval > $value_ts)
    {
	dbg("lastclock:$lastclock value calculation not needed");
	exit(SUCCESS);
    }

    info("lastclock:$lastclock value_ts:$value_ts interval:$interval");
}

sub get_down_count
{
    my $itemid_src = shift;
    my $itemid_dst = shift;
    my $from = shift;
    my $till = shift;

    my $eventtimes = get_eventtimes($itemid_src, $from, $till);

    my $count = 0;

    my $total = scalar(@$eventtimes);
    my $i = 0;
    while ($i < $total)
    {
	my $event_from = $eventtimes->[$i++];
	my $event_till = $eventtimes->[$i++];

	my $res = db_select("select count(*) from history_uint where itemid=$itemid_src and clock between $event_from and $event_till and value=" . DOWN);

	$count += ($res->fetchrow_array)[0];
    }

    return $count;
}

sub dbg
{
    return unless (defined($OPTS{debug}));

    __log(join('', __ts(), ' [', __script(), ' ', $tld, '] [DBG] ', @_, "\n"));
}

sub info
{
    my $msg = join('', @_);

    __log(join('', __ts(), ' [', __script(), ' ', $tld, '] [INF] ', @_, "\n"));
}

sub warn
{
    my $msg = join('', @_);

    __log(join('', __ts(), ' [', __script(), ' ', $tld, '] [WRN] ', @_, "\n"));
}

sub fail
{
    my $msg = join('', @_);

    __log(join('', __ts(), ' [', __script(), ' ', $tld, '] [ERR] ', @_, "\n"));

    exit FAIL;
}

sub exit_if_running
{
    return if (defined($OPTS{test}));

    my $script = __script();
    my $cmd = "pgrep -fl \"/$script.* $tld( |\$)\" | grep -v -- --test";

    $cmd =~ s/\./\\./g;

    my ($stdout, $stderr) = capture_exec($cmd);
    $stdout = trim($stdout);
    $stderr = trim($stderr);

    my @stdout_lines = split("\n", $stdout);
    my $numproc = scalar(@stdout_lines);

    dbg("stdout:'$stdout' stderr:'$stderr' cmd:'$cmd' numproc:$numproc ARGV:'", join(" ", @ARGV), "'");

    fail("cannot check if script is running, command \"$cmd\" failed ($stderr)") unless ($stderr eq "" and $numproc > 0);

    unless ($numproc == 1) # mind myself
    {
	info("already running");
	exit(SUCCESS);
    }
}

sub trim
{
    my $out = shift;

    $out =~ s/^\s+//;
    $out =~ s/\s+$//;

    return $out;
}

sub parse_opts
{
    usage() unless (GetOptions(\%OPTS, "debug!", "stdout!", "test!") and defined($ARGV[0]));
    $tld = $ARGV[0];
}

sub usage
{
    print("usage: $0 <tld> [options]\n");
    print("Options:\n");
    print("    --debug    print more details\n");
    print("    --stdout   print output to stdout instead of log file\n");
    print("    --test     run the script in test mode, this means:\n");
    print("               - skip checks if need to recalculate value\n");
    print("               - do not send the value to the server\n");
    print("               - print the output to stdout instead of logging it\n");
    exit(FAIL);
}

#################
# Internal subs #
#################

sub __log
{
    my $msg = shift;

    if (defined($OPTS{'test'}) or defined($OPTS{'stdout'}))
    {
	print($msg);
	return;
    }

    my $OUTFILE;

    my $script = __script();
    $script =~ s,\.pl$,,;

    my $file = $config->{'slv'}->{'logdir'} . '/' . $tld . '-' . $script . '.log';

    open $OUTFILE, '>>', $file or fail("cannot open $file: $!");

    print {$OUTFILE} $msg or fail("cannot write to $file: $!");

    close $OUTFILE or fail("cannot close $file: $!");
}

sub get_macro
{
    my $m = shift;

    $result = $zabbix->get('usermacro', {globalmacro => 1, output => 'extend', filter => {macro => $m}});

    fail("cannot get value of macro '$m'") unless ($result->{'value'});

    return $result->{'value'};
}

# organize values from all hosts grouped by name server and return "name server"->values hash
#
# E. g.:
#
# 'g.ns.se.,2001:6b0:e:3::1' => [
#          205
#    ];
# 'b.ns.se.,192.36.133.107' => [
#          -102
#          304
#    ];
sub get_ns_values
{
    my $items_ref = shift;
    my $from = shift;
    my $till = shift;
    my $all_ns_items = shift;

    my %result;

    if (0 < scalar(keys(%$items_ref)))
    {
	my $items_str = "";
	foreach my $itemid (keys(%$items_ref))
	{
	    $items_str .= "," if ("" ne $items_str);
	    $items_str .= $itemid;
	}

	my $res = db_select("select itemid,value from history where itemid in ($items_str) and clock between $from and $till");

	while (my @row = $res->fetchrow_array)
	{
	    my $itemid = $row[0];
	    my $value = $row[1];

	    my $ns = '';
	    my $last = 0;
	    foreach my $hostid (keys(%$all_ns_items))
	    {
		foreach my $i (keys(%{$all_ns_items->{$hostid}}))
		{
		    if ($i == $itemid)
		    {
			$ns = $all_ns_items->{$hostid}{$i};
			$last = 1;
			last;
		    }
		}
		last if ($last == 1);
	    }

	    fail("internal error: name server of item $itemid not found") if ($ns eq "");

	    if (exists($result{$ns}))
	    {
		$result{$ns} = [@{$result{$ns}}, $value];
	    }
	    else
	    {
		$result{$ns} = [$value];
	    }
	}
    }

    return \%result;
}

# return an array reference of values of items for the particular period
sub get_values
{
    my $items_ref = shift;
    my $from = shift;
    my $till = shift;

    my @result;

    if (0 < scalar(keys(%$items_ref)))
    {
	my $items_str = "";
	foreach my $itemid (keys(%$items_ref))
	{
	    $items_str .= "," if ("" ne $items_str);
	    $items_str .= $itemid;
	}

	my $res = db_select("select value from history where itemid in ($items_str) and clock between $from and $till");

	while (my @row = $res->fetchrow_array)
	{
	    push(@result, $row[0]);
	}
    }

    return \@result;
}

sub get_online_items
{
    my $hostids_ref = shift;
    my $all_items = shift;
			    
    my %result;

    foreach my $hostid (@$hostids_ref)
    {
	fail("internal error: no hostid $hostid in input items") unless ($all_items->{$hostid});

	foreach my $itemid (keys(%{$all_items->{$hostid}}))
	{
	    $result{$itemid} = $all_items->{$hostid}{$itemid};
	}
    }

    return \%result;
}

# return items hash from all hosts grouped by hostid (hostid => hash of its items (itemid => ns)):
#
# hostid1
#   32389 => 'i.ns.se.,2001:67c:1010:5::53',
#   32386 => 'g.ns.se.,130.239.5.114',
#   ...
# hostid2
#   ...
# ...
sub get_all_ns_items
{
    my $nss_ref = shift; # array reference of name servers ("name,IP")
    my $cfg_key_in = shift;

    my @keys;
    push(@keys, $cfg_key_in . $_ . ']') foreach (@$nss_ref);

    my $result = $zabbix->get('item', {output => ['hostid', 'key_'], templated => 0, filter => {key_ => \@keys}});

    my %all_ns_items;

    if ('ARRAY' eq ref($result))
    {
	foreach (@$result)
	{
	    my $hostid = $_->{'hostid'};
	    my $itemid = $_->{'itemid'};
	    my $ns = get_ns_from_key($_->{'key_'});

	    $all_ns_items{$hostid}{$itemid} = $ns;
	}
    }
    elsif (defined($result->{'key_'}))
    {
	my $hostid = $result->{'hostid'};
	my $itemid = $result->{'itemid'};
	my $ns = get_ns_from_key($result->{'key_'});

	$all_ns_items{$hostid}{$itemid} = $ns;
    }
    else
    {
        fail("no input items found (searched for: " . join(', ', @keys) . ")");
    }

    return \%all_ns_items;
}

# return items hash from all hosts (hostid => hash of its item (itemid => '')):
#
# hostid1
#   32389 => ''
# hostid2
#   32419 => ''
# ...
sub get_all_items
{
    my $key = shift;

    my $result = $zabbix->get('item', {output => ['hostid', 'key_'], templated => 0, filter => {key_ => $key}});

    my %all_items;

    if ('ARRAY' eq ref($result))
    {
	foreach (@$result)
	{
	    my $hostid = $_->{'hostid'};
	    my $itemid = $_->{'itemid'};

	    $all_items{$hostid}{$itemid} = '';
	}
    }
    elsif (defined($result->{'key_'}))
    {
	my $hostid = $result->{'hostid'};
	my $itemid = $result->{'itemid'};

	$all_items{$hostid}{$itemid} = '';
    }
    else
    {
        fail("no input items found (searched for: $key)");
    }

    return \%all_items;
}

# get array of key nameservers ('i.ns.se.,130.239.5.114', ...)
sub get_nss
{
    my $tld = shift;
    my $cfg_key_out = shift;

    my $result = $zabbix->get('item', {output => ['key_'], host => $tld, startSearch => 1, search => {key_ => $cfg_key_out}});

    my @nss;
    if ('ARRAY' eq ref($result))
    {
	push(@nss, get_ns_from_key($_->{'key_'})) foreach (@$result);
    }
    elsif (defined($result->{'key_'}))
    {
	push(@nss, get_ns_from_key($result->{'key_'}));
    }
    else
    {
	fail("no output items at host $tld ($cfg_key_out.*)");
    }

    return \@nss;
}

sub __script
{
    my $script = $0;

    $script =~ s,.*/([^/]*)$,$1,;

    return $script;
}

sub __ts
{   
    my ($sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst) = localtime(time);

    $year += 1900;
    $mon++;
    return sprintf("[%4.2d%2.2d%2.2d:%2.2d%2.2d%2.2d]", $year, $mon, $mday, $hour, $min, $sec);
}

# return incidents (start and end times) in array:
#
# [
#   1386066210, 1386340110,
#   1386340290, 1386340470
# ]
#
# if the incident is still onoing at the passed $from time that time will be used
# as end time
sub get_eventtimes
{
    my $itemid = shift;
    my $from = shift;
    my $till = shift;

    my $result = $zabbix->get('trigger', {'itemids' => $itemid, 'output' => ['triggerid'], 'filter' => {'priority' => TRIGGER_SEVERITY_NOT_CLASSIFIED}});

    if ('ARRAY' eq ref($result))
    {
	fail("item $itemid has more than one not classified triggers");
    }

    if (!defined($result->{'triggerid'}))
    {
	fail("item $itemid has no not classified triggers");
    }

    my $triggerid = $result->{'triggerid'};

    my @eventtimes;

    # select events, where time_from < filter_from and value = TRIGGER_VALUE_TRUE
    my $res = db_select("select clock,value from events where objectid=$triggerid and clock<$from and value_changed=".TRIGGER_VALUE_CHANGED_YES." order by clock desc limit 1");

    while (my @row = $res->fetchrow_array)
    {
	my $clock = $row[0];
	my $value = $row[1];

	push(@eventtimes, $clock) if ($value == TRIGGER_VALUE_TRUE);
    }

    $result = $zabbix->get('event', 
			   {'triggerids' => [$triggerid],
			    'selectTriggers' => API_OUTPUT_REFER,
			    'time_from' => $from,
			    'time_till' => $till,
			    'output' => 'extend',
			    'filter' => {'value_changed' => TRIGGER_VALUE_CHANGED_YES}});

    my @unsorted_eventtimes;
    if ('ARRAY' eq ref($result))
    {
	push(@unsorted_eventtimes, $_->{'clock'}) foreach (@$result);
    }
    elsif (defined($result->{'clock'}))
    {
	push(@unsorted_eventtimes, $result->{'clock'});
    }

    push(@eventtimes, $_) foreach (sort(@unsorted_eventtimes));
    push(@eventtimes, $till) if ((scalar(@eventtimes) % 2) != 0);

    return \@eventtimes;
}

1;
