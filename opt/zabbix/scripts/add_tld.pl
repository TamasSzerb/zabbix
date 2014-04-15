#!/usr/bin/perl
#
# - DNS availability test		(data collection)	rsm.dns.udp			(simple, every minute)
#								rsm.dns.tcp			(simple, every 50 minutes)
#								rsm.dns.udp.rtt		(trapper)
#								rsm.dns.tcp.rtt		-|-
#								rsm.dns.udp.upd		-|-
# - RDDS availability test		(data collection)	rsm.rdds			(simple, every minutes)
#   (also RDDS43 and RDDS80					rsm.rdds.43.ip		(trapper)
#   availability at a particular				rsm.rdds.43.rtt		-|-
#   minute)							rsm.rdds.43.upd		-|-
#								rsm.rdds.80.ip		-|-
#								rsm.rdds.80.rtt		-|-
#
# - DNS NS availability			(given minute)		rsm.slv.dns.ns.avail	-|-	+
# - DNS NS monthly availability		(monthly)		rsm.slv.dns.ns.month	-|-	+
# - DNS monthly resolution RTT		(monthly)		rsm.slv.dns.ns.rtt.udp.month-|-	+
# - DNS monthly resolution RTT (TCP)	(monthly, TCP)		rsm.slv.dns.ns.rtt.tcp.month-|-	+
# - DNS monthly update time		(monthly)		rsm.slv.dns.ns.upd.month	-|-	+
# - DNS availability			(given minute)		rsm.slv.dns.avail		-|-	+
# - DNS rolling week			(rolling week)		rsm.slv.dns.rollweek	-|-	+
# - DNSSEC proper resolution		(given minute)		rsm.slv.dnssec.avail	-|-	+
# - DNSSEC rolling week			(rolling week)		rsm.slv.dnssec.rollweek	-|-	+
#
# - RDDS availability			(given minute)		rsm.slv.rdds.avail		-|-	-
# - RDDS rolling week			(rolling week)		rsm.slv.rdds.rollweek	-|-	-
# - RDDS43 monthly resolution RTT	(monthly)		rsm.slv.rdds.43.rtt.month	-|-	-
# - RDDS80 monthly resolution RTT	(monthly)		rsm.slv.rdds.80.rtt.month	-|-	-
# - RDDS monthly update time		(monthly)		rsm.slv.rdds.upd.month	-|-	-
#

use lib '/opt/zabbix/scripts';

use strict;
use warnings;
use Zabbix;
use Getopt::Long;
use MIME::Base64;
use Digest::MD5 qw(md5_hex);
use Expect;
use Data::Dumper;
use RSM;

use constant LINUX_TEMPLATEID => 10001;

use constant VALUE_TYPE_AVAIL => 0;
use constant VALUE_TYPE_PERC => 1;

use constant ZBX_EC_DNS_NS_NOREPLY    => -200; # no reply from Name Server
use constant ZBX_EC_DNS_NS_ERRREPLY   => -201; # invalid reply from Name Server
use constant ZBX_EC_DNS_NS_NOTS       => -202; # no UNIX timestamp
use constant ZBX_EC_DNS_NS_ERRTS      => -203; # invalid UNIX timestamp
use constant ZBX_EC_DNS_NS_ERRSIG     => -204; # DNSSEC error
use constant ZBX_EC_DNS_RES_NOREPLY   => -205; # no reply from resolver
use constant ZBX_EC_DNS_RES_NOADBIT   => -206; # no AD bit in the answer from resolver
use constant ZBX_EC_RDDS43_NOREPLY    => -200; # no reply from RDDS43 server
use constant ZBX_EC_RDDS43_NONS       => -201; # Whois server returned no NS
use constant ZBX_EC_RDDS43_NOTS       => -202; # no Unix timestamp
use constant ZBX_EC_RDDS43_ERRTS      => -203; # invalid Unix timestamp
use constant ZBX_EC_RDDS80_NOREPLY    => -204; # no reply from RDDS80 server
use constant ZBX_EC_RDDS_ERRRES       => -205; # cannot resolve a Whois host
use constant ZBX_EC_RDDS80_NOHTTPCODE => -206; # no HTTP response code in response from RDDS80 server
use constant ZBX_EC_RDDS80_EHTTPCODE  => -207; # invalid HTTP response code in response from RDDS80 server
use constant ZBX_EC_EPP_NO_IP         => -200; # IP is missing for EPP server
use constant ZBX_EC_EPP_CONNECT       => -201; # cannot connect to EPP server
use constant ZBX_EC_EPP_CRYPT         => -202; # invalid certificate or private key
use constant ZBX_EC_EPP_FIRSTTO       => -203; # first message timeout
use constant ZBX_EC_EPP_FIRSTINVAL    => -204; # first message is invalid
use constant ZBX_EC_EPP_LOGINTO       => -205; # LOGIN command timeout
use constant ZBX_EC_EPP_LOGININVAL    => -206; # invalid reply to LOGIN command
use constant ZBX_EC_EPP_UPDATETO      => -207; # UPDATE command timeout
use constant ZBX_EC_EPP_UPDATEINVAL   => -208; # invalid reply to UPDATE command
use constant ZBX_EC_EPP_INFOTO        => -209; # INFO command timeout
use constant ZBX_EC_EPP_INFOINVAL     => -210; # invalid reply to INFO command

use constant cfg_probe_status_delay => 60;
use constant cfg_default_rdds_ns_string => 'Name Server:';

use constant rsm_host => 'rsm'; # global config history
use constant rsm_group => 'rsm';

my %OPTS;
my $rv = GetOptions(\%OPTS,
		    "tld=s",
		    "rdds43-servers=s",
		    "rdds80-servers=s",
		    "dns-test-prefix=s",
		    "rdds-test-prefix=s",
		    "ipv4!",
		    "ipv6!",
		    "dnssec!",
		    "epp-servers=s",
		    "epp-user=s",
		    "epp-cert=s",
		    "epp-privkey=s",
		    "epp-commands=s",
		    "epp-serverid=s",
		    "epp-test-prefix=s",
		    "epp-servercert=s",
		    "ns-servers-v4=s",
		    "ns-servers-v6=s",
		    "rdds-ns-string=s",
		    "only-cron!",
		    "verbose!",
		    "quiet!",
		    "help|?");

usage() if ($OPTS{'help'} or not $rv);

validate_input();
lc_options();

# TODO: add command-line parameters:
# - rdds43 hosts (2nd parameter of rsm.rdds)
# - rdds80 hosts (3rd parameter of rsm.rdds)
# ...

my $main_templateid;
my $ns_servers;

# Expect stuff for EPP
my $exp_timeout = 3;
my $exp_command = '/opt/zabbix/bin/rsm_epp_enc';
my $exp_output;

use constant value_mappings => {'rsm_dns' => 13,
				'rsm_probe' => 14,
				'rsm_rdds_rttudp' => 15,
				'rsm_avail' => 16,
				'rsm_rdds_avail' => 18,
                                'rsm_epp' => 19};

use constant APP_SLV_MONTHLY => 'SLV monthly';
use constant APP_SLV_ROLLWEEK => 'SLV rolling week';
use constant APP_SLV_PARTTEST => 'SLV particular test';

my $config = get_rsm_config();
my $zabbix = Zabbix->new({'url' => $config->{'zapi'}->{'url'}, user => $config->{'zapi'}->{'user'}, password => $config->{'zapi'}->{'password'}});

create_cron_items();
exit if (defined($OPTS{'only-cron'}));

my $proxies = get_proxies_list();

pfail("cannot find existing proxies") if (scalar(keys %{$proxies}) == 0);

create_macro('{$RSM.IP4.MIN.PROBE.ONLINE}', 25, undef);
create_macro('{$RSM.IP6.MIN.PROBE.ONLINE}', 20, undef);

create_macro('{$RSM.IP4.MIN.SERVERS}', 4, undef);
create_macro('{$RSM.IP6.MIN.SERVERS}', 4, undef);
create_macro('{$RSM.IP4.REPLY.MS}', 200, undef);
create_macro('{$RSM.IP6.REPLY.MS}', 200, undef);

create_macro('{$RSM.DNS.TCP.RTT.LOW}', 1500, undef);
create_macro('{$RSM.DNS.TCP.RTT.HIGH}', 7500, undef);
create_macro('{$RSM.DNS.UDP.RTT.LOW}', 500, undef);
create_macro('{$RSM.DNS.UDP.RTT.HIGH}', 2500, undef);
create_macro('{$RSM.DNS.UDP.DELAY}', 60, undef);
create_macro('{$RSM.DNS.TCP.DELAY}', 3000, undef);
create_macro('{$RSM.DNS.UPDATE.TIME}', 3600, undef);
create_macro('{$RSM.DNS.PROBE.ONLINE}', 20, undef);
create_macro('{$RSM.DNS.AVAIL.MINNS}', 2, undef);
create_macro('{$RSM.DNS.ROLLWEEK.SLA}', 240, undef);

create_macro('{$RSM.RDDS.RTT.LOW}', 2000, undef);
create_macro('{$RSM.RDDS.RTT.HIGH}', 10000, undef);
create_macro('{$RSM.RDDS.DELAY}', 300, undef);
create_macro('{$RSM.RDDS.UPDATE.TIME}', 3600, undef);
create_macro('{$RSM.RDDS.PROBE.ONLINE}', 10, undef);
create_macro('{$RSM.RDDS.ROLLWEEK.SLA}', 48, undef);
create_macro('{$RSM.RDDS.MAXREDIRS}', 10, undef);

create_macro('{$RSM.EPP.DELAY}', 300, undef);
create_macro('{$RSM.EPP.LOGIN.RTT.LOW}', 4000, undef);
create_macro('{$RSM.EPP.LOGIN.RTT.HIGH}', 20000, undef);
create_macro('{$RSM.EPP.UPDATE.RTT.LOW}', 4000, undef);
create_macro('{$RSM.EPP.UPDATE.RTT.HIGH}', 20000, undef);
create_macro('{$RSM.EPP.INFO.RTT.LOW}', 2000, undef);
create_macro('{$RSM.EPP.INFO.RTT.HIGH}', 10000, undef);
create_macro('{$RSM.EPP.PROBE.ONLINE}', 5, undef);
create_macro('{$RSM.EPP.ROLLWEEK.SLA}', 48, undef);

create_macro('{$RSM.PROBE.ONLINE.DELAY}', 180, undef);

create_macro('{$RSM.TRIG.DOWNCOUNT}', '#1', undef);
create_macro('{$RSM.TRIG.UPCOUNT}', '#3', undef);

create_macro('{$RSM.INCIDENT.DNS.FAIL}', 3, undef);
create_macro('{$RSM.INCIDENT.DNS.RECOVER}', 3, undef);
create_macro('{$RSM.INCIDENT.DNSSEC.FAIL}', 3, undef);
create_macro('{$RSM.INCIDENT.DNSSEC.RECOVER}', 3, undef);
create_macro('{$RSM.INCIDENT.RDDS.FAIL}', 2, undef);
create_macro('{$RSM.INCIDENT.RDDS.RECOVER}', 2, undef);
create_macro('{$RSM.INCIDENT.EPP.FAIL}', 2, undef);
create_macro('{$RSM.INCIDENT.EPP.RECOVER}', 2, undef);

create_macro('{$RSM.SLV.DNS.UDP.RTT}', 99, undef);
create_macro('{$RSM.SLV.DNS.TCP.RTT}', 99, undef);
create_macro('{$RSM.SLV.NS.AVAIL}', 99, undef);
create_macro('{$RSM.SLV.RDDS43.RTT}', 99, undef);
create_macro('{$RSM.SLV.RDDS80.RTT}', 99, undef);
create_macro('{$RSM.SLV.RDDS.UPD}', 99, undef);
create_macro('{$RSM.SLV.DNS.NS.UPD}', 99, undef);
create_macro('{$RSM.SLV.EPP.LOGIN}', 99, undef);
create_macro('{$RSM.SLV.EPP.UPDATE}', 99, undef);
create_macro('{$RSM.SLV.EPP.INFO}', 99, undef);

create_macro('{$RSM.ROLLWEEK.THRESHOLDS}', '0,5,10,25,50,75,100', undef);
create_macro('{$RSM.PROBE.AVAIL.LIMIT}', '60', undef);

my $result;
my $m = '{$RSM.DNS.UDP.DELAY}';
unless (($result = $zabbix->get('usermacro', {'globalmacro' => 1, output => 'extend', 'filter' => {'macro' => $m}}))
	and defined $result->{'value'}) {
    pfail('cannot get macro ', $m);
}
my $cfg_dns_udp_delay = $result->{'value'};
$m = '{$RSM.DNS.TCP.DELAY}';
unless (($result = $zabbix->get('usermacro', {'globalmacro' => 1, output => 'extend', 'filter' => {'macro' => $m}}))
	and defined $result->{'value'}) {
    pfail('cannot get macro ', $m);
}
my $cfg_dns_tcp_delay = $result->{'value'};
$m = '{$RSM.RDDS.DELAY}';
unless (($result = $zabbix->get('usermacro', {'globalmacro' => 1, output => 'extend', 'filter' => {'macro' => $m}}))
	and defined $result->{'value'}) {
    pfail('cannot get macro ', $m);
}
my $cfg_rdds_delay = $result->{'value'};
$m = '{$RSM.EPP.DELAY}';
unless (($result = $zabbix->get('usermacro', {'globalmacro' => 1, output => 'extend', 'filter' => {'macro' => $m}}))
	         and defined $result->{'value'}) {
        pfail('cannot get macro ', $m);
    }
my $cfg_epp_delay = $result->{'value'};

my $rsm_groupid = create_group(rsm_group);

my $rsm_hostid = create_host({'groups' => [{'groupid' => $rsm_groupid}],
			      'host' => rsm_host,
			      'interfaces' => [{'type' => 1, 'main' => 1, 'useip' => 1, 'ip'=> '127.0.0.1', 'dns' => '', 'port' => '10050'}]});

# calculated items, configuration history (TODO: rename host to something like config_history)
create_rsm_items($rsm_hostid);

$ns_servers = get_ns_servers($OPTS{'tld'});

my $root_servers_macros = update_root_servers();

$main_templateid = create_main_template($OPTS{'tld'}, $ns_servers);

my $tld_groupid = create_group('TLD '.$OPTS{'tld'});

my $tlds_groupid = create_group('TLDs');

my $tld_hostid = create_host({'groups' => [{'groupid' => $tld_groupid}, {'groupid' => $tlds_groupid}],
			      'host' => $OPTS{'tld'},
			      'interfaces' => [{'type' => 1, 'main' => 1, 'useip' => 1, 'ip'=> '127.0.0.1', 'dns' => '', 'port' => '10050'}]});

create_slv_items($ns_servers, $tld_hostid, $OPTS{'tld'});

my $probes_groupid = create_group('Probes');

my $probes_mon_groupid = create_group('Probes - Mon');

my $proxy_mon_templateid = create_template('Template Proxy Health', LINUX_TEMPLATEID);

my $item_key = 'zabbix[proxy,{$RSM.PROXY_NAME},lastaccess]';

my $options = {'name' => 'Availability of $2 Probe',
                                          'key_'=> $item_key,
                                          'hostid' => $proxy_mon_templateid,
                                          'applications' => [get_application_id('Probe Availability', $proxy_mon_templateid)],
                                          'type' => 5, 'value_type' => 3,
                                          'units' => 'unixtime', delay => '60'};

create_item($options);

$options = { 'description' => 'PROBE {HOST.NAME}: Probe {$RSM.PROXY_NAME} is not available',
                     'expression' => '{Template Proxy Health:'.$item_key.'.fuzzytime(2m)}=0',
                    'priority' => '4',
            };

create_trigger($options);

foreach my $proxyid (sort keys %{$proxies}) {
    my $probe_name = $proxies->{$proxyid}->{'host'};

    print $proxyid."\n";
    print $proxies->{$proxyid}->{'host'}."\n";

    my $proxy_groupid = create_group($probe_name);

    my $probe_templateid = create_probe_template($probe_name);
    my $probe_status_templateid = create_probe_status_template($probe_name, $probe_templateid);

    create_host({'groups' => [{'groupid' => $proxy_groupid}, {'groupid' => $probes_groupid}],
                                          'templates' => [{'templateid' => $probe_status_templateid}],
                                          'host' => $probe_name,
                                          'proxy_hostid' => $proxyid,
                                          'interfaces' => [{'type' => 1, 'main' => 1, 'useip' => 1,
							    'ip'=> '127.0.0.1',
							    'dns' => '', 'port' => '10050'}]
		});

    my $hostid = create_host({'groups' => [{'groupid' => $probes_mon_groupid}],
                                          'templates' => [{'templateid' => $proxy_mon_templateid}],
                                          'host' => $probe_name.' - mon',
                                          'interfaces' => [{'type' => 1, 'main' => 1, 'useip' => 1,
                                                            'ip'=> $proxies->{$proxyid}->{'interfaces'}[0]->{'ip'},
                                                            'dns' => '', 'port' => '10050'}]
            		    });

    create_macro('{$RSM.PROXY_NAME}', $probe_name, $hostid, 1);

    create_host({'groups' => [{'groupid' => $tld_groupid}, {'groupid' => $proxy_groupid}],
                                          'templates' => [{'templateid' => $main_templateid}, {'templateid' => $probe_templateid}],
                                          'host' => $OPTS{'tld'}.' '.$probe_name,
                                          'proxy_hostid' => $proxyid,
                                          'interfaces' => [{'type' => 1, 'main' => 1, 'useip' => 1, 'ip'=> '127.0.0.1', 'dns' => '', 'port' => '10050'}]});
}

create_probe_status_host($probes_mon_groupid);

########### FUNCTIONS ###############

sub create_host {
    my $options = shift;

    unless ($zabbix->exist('host',{'name' => $options->{'host'}})) {
	print("creating host '", $options->{'host'}, "'\n");
        my $result = $zabbix->create('host', $options);

        return $result->{'hostids'}[0];
    }

    my $result = $zabbix->get('host', {'output' => ['hostid'], 'filter' => {'host' => [$options->{'host'}]}});

    pfail("more than one host named \"", $options->{'host'}, "\" found") if ('ARRAY' eq ref($result));
    pfail("host \"", $options->{'host'}, "\" not found") unless (defined($result->{'hostid'}));

    $options->{'hostid'} = $result->{'hostid'};
    delete($options->{'interfaces'});
    $result = $zabbix->update('host', $options);

    my $hostid = $result->{'hostid'} ? $result->{'hostid'} : $options->{'hostid'};

    return $hostid;
}

sub get_application_id {
    my $name = shift;
    my $templateid = shift;

    unless ($zabbix->exist('application',{'name' => $name, 'hostid' => $templateid})) {
	my $result = $zabbix->create('application', {'name' => $name, 'hostid' => $templateid});
	return $result->{'applicationids'}[0];
    }

    my $result = $zabbix->get('application', {'hostids' => [$templateid], 'filter' => {'name' => $name}});
    return $result->{'applicationid'};
}

sub get_proxies_list {
    my $proxies_list;

    $proxies_list = $zabbix->get('proxy',{'output' => ['proxyid', 'host'], 'selectInterfaces' => ['ip'], 'preservekeys' => 1 });

    return $proxies_list;
}

sub get_ns_servers {
    my $tld = shift;

    if ($OPTS{'ns-servers-v4'} or $OPTS{'ns-servers-v6'}) {
	if ($OPTS{'ns-servers-v4'} and $OPTS{'ipv4'} == 1) {
	    my @nsservers = split(/\s/, $OPTS{'ns-servers-v4'});
	    foreach my $ns (@nsservers) {
		my @entries = split(/,/, $ns);

		my $exists = 0;
		foreach my $ip (@{$ns_servers->{$entries[0]}{'v4'}}) {
		    if ($ip eq $entries[1]) {
			$exists = 1;
			last;
		    }
		}

		push(@{$ns_servers->{$entries[0]}{'v4'}}, $entries[1]) unless ($exists);
	    }
	}

	if ($OPTS{'ns-servers-v6'} and $OPTS{'ipv6'} == 1) {
	    my @nsservers = split(/\s/, $OPTS{'ns-servers-v6'});
	    foreach my $ns (@nsservers) {
		my @entries = split(/,/, $ns);

		my $exists = 0;
		foreach my $ip (@{$ns_servers->{$entries[0]}{'v6'}}) {
		    if ($ip eq $entries[1]) {
			$exists = 1;
			last;
		    }
		}

		push(@{$ns_servers->{$entries[0]}{'v6'}}, $entries[1]) unless ($exists);
	    }
	}
    } else {
	my $nsservers = `dig $tld NS +short`;
	my @nsservers = split(/\n/,$nsservers);

	foreach (my $i = 0;$i<=$#nsservers; $i++) {
	    if ($OPTS{'ipv4'} == 1) {
		my $ipv4 = `dig $nsservers[$i] A +short`;
		my @ipv4 = split(/\n/, $ipv4);

		@{$ns_servers->{$nsservers[$i]}{'v4'}} = @ipv4 if scalar @ipv4;
	    }

	    if ($OPTS{'ipv6'} == 1) {
		my $ipv6 = `dig $nsservers[$i] AAAA +short` if $OPTS{'ipv6'};
		my @ipv6 = split(/\n/, $ipv6);

		@{$ns_servers->{$nsservers[$i]}{'v6'}} = @ipv6 if scalar @ipv6;
	    }
	}
    }

    return $ns_servers;
}

sub create_group {
    my $name = shift;

    my $groupid;

    unless ($zabbix->exist('hostgroup',{'name' => $name})) {
        my $result = $zabbix->create('hostgroup', {'name' => $name});
	$groupid = $result->{'groupids'}[0];
    }
    else {
	my $result = $zabbix->get('hostgroup', {'filter' => {'name' => [$name]}});
        $groupid = $result->{'groupid'};
    }

    return $groupid;
}

sub create_template {
    my $name = shift;
    my $child_templateid = shift;

    my ($result, $templateid, $options, $groupid);

    unless ($zabbix->exist('hostgroup', {'name' => 'Templates - TLD'})) {
        $result = $zabbix->create('hostgroup', {'name' => 'Templates - TLD'});
        $groupid = $result->{'groupids'}[0];
    }
    else {
        $result = $zabbix->get('hostgroup', {'filter' => {'name' => 'Templates - TLD'}});
        $groupid = $result->{'groupid'};
    }

    unless ($zabbix->exist('template',{'host' => $name})) {
        $options = {'groups'=> {'groupid' => $groupid}, 'host' => $name};

        $options->{'templates'} = [{'templateid' => $child_templateid}] if defined $child_templateid;

        $result = $zabbix->create('template', $options);
        $templateid = $result->{'templateids'}[0];
    }
    else {
        $result = $zabbix->get('template', {'filter' => {'host' => $name}});
        $templateid = $result->{'templateid'};

        $options = {'templateid' => $templateid, 'groups'=> {'groupid' => $groupid}, 'host' => $name};
        $options->{'templates'} = [{'templateid' => $child_templateid}] if defined $child_templateid;

        $result = $zabbix->update('template', $options);
        $templateid = $result->{'templateids'}[0];
    }

    return $templateid;
}

sub create_item {
    my $options = shift;
    my $result;

    pfail("cannot add item: hostid not specified\n", Dumper($options)) unless (defined($options->{'hostid'}));

    if ($zabbix->exist('item', {'hostid' => $options->{'hostid'}, 'key_' => $options->{'key_'}})) {
	$result = $zabbix->get('item', {'hostids' => $options->{'hostid'}, 'filter' => {'key_' => $options->{'key_'}}});

	if ('ARRAY' eq ref($result))
	{
	    pfail("Request: ", Dumper($options),
		  "returned more than one item with key ", $options->{'key_'}, ":\n",
		  Dumper($result));
	}

	$options->{'itemid'} = $result->{'itemid'};

	$result = $zabbix->update('item', $options);
    }
    else {
        $result = $zabbix->create('item', $options);
    }

    $result = ${$result->{'itemids'}}[0] if (defined(${$result->{'itemids'}}[0]));

    pfail("cannot create item:\n", Dumper($options)) if (ref($result) ne '' or $result eq '');

    return $result;
}

sub create_trigger {
    my $options = shift;
    my $result;

    if ($zabbix->exist('trigger',{'expression' => $options->{'expression'}})) {
        $result = $zabbix->update('trigger', $options);
    }
    else {
        $result = $zabbix->create('trigger', $options);
    }

#    pfail("cannot create trigger:\n", Dumper($options)) if (ref($result) ne '' or $result eq '');

    return $result;
}

sub create_item_dns_rtt {
    my $ns_name = shift;
    my $ip = shift;
    my $templateid = shift;
    my $template_name = shift;
    my $proto = shift;
    my $ipv = shift;

    pfail("undefined template ID passed to create_item_dns_rtt()") unless ($templateid);
    pfail("no protocol parameter specified to create_item_dns_rtt()") unless ($proto);

    my $proto_lc = lc($proto);
    my $proto_uc = uc($proto);

    my $item_key = 'rsm.dns.'.$proto_lc.'.rtt[{$RSM.TLD},'.$ns_name.','.$ip.']';

    my $options = {'name' => 'DNS RTT of $2 ($3) ('.$proto_uc.')',
                                              'key_'=> $item_key,
                                              'hostid' => $templateid,
                                              'applications' => [get_application_id('DNS RTT ('.$proto_uc.')', $templateid)],
                                              'type' => 2, 'value_type' => 0,
                                              'valuemapid' => value_mappings->{'rsm_dns'}};

    create_item($options);

    $options = { 'description' => 'DNS-RTT-'.$proto_uc.' {HOST.NAME}: 5.1.1 Step 5 - No reply from Name Server '.$ns_name.' ['.$ip.']',
                         'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_DNS_NS_NOREPLY.
					    '&{'.$template_name.':'.'probe.configvalue[RSM.IP'.$ipv.'.ENABLED]'.'.last(0)}=1',
                        'priority' => '2',
                };

    create_trigger($options);

    $options = { 'description' => 'DNS-RTT-'.$proto_uc.' {HOST.NAME}: 5.1.1 Step 5 - Invalid reply from Name Server '.$ns_name.' ['.$ip.']',
                         'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_DNS_NS_ERRREPLY.
                                            '&{'.$template_name.':'.'probe.configvalue[RSM.IP'.$ipv.'.ENABLED]'.'.last(0)}=1',
                        'priority' => '2',
                };

    create_trigger($options);

    $options = { 'description' => 'DNS-RTT-'.$proto_uc.' {HOST.NAME}: 5.1.1 Step 6 - UNIX timestamp is missing from '.$ns_name.' ['.$ip.']',
                         'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_DNS_NS_NOTS.
                                            '&{'.$template_name.':'.'probe.configvalue[RSM.IP'.$ipv.'.ENABLED]'.'.last(0)}=1',
                        'priority' => '2',
                };

    create_trigger($options);

    $options = { 'description' => 'DNS-RTT-'.$proto_uc.' {HOST.NAME}: 5.1.1 Step 6 - Invalid UNIX timestamp from '.$ns_name.' ['.$ip.']',
                         'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_DNS_NS_ERRTS.
                                            '&{'.$template_name.':'.'probe.configvalue[RSM.IP'.$ipv.'.ENABLED]'.'.last(0)}=1',
                        'priority' => '2',
                };

    create_trigger($options);

    if (defined($OPTS{'dnssec'})) {
	$options = { 'description' => 'DNS-RTT-'.$proto_uc.' {HOST.NAME}: 5.1.1 Step 7 - DNSSEC error from '.$ns_name.' ['.$ip.']',
		     'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_DNS_NS_ERRSIG.
                                            '&{'.$template_name.':'.'probe.configvalue[RSM.IP'.$ipv.'.ENABLED]'.'.last(0)}=1',
		     'priority' => '2',
	};

	create_trigger($options);
    }

    $options = { 'description' => 'DNS-RTT-'.$proto_uc.' {HOST.NAME}: 5.1.1 Step 5 - No reply from resolver',
                         'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_DNS_RES_NOREPLY.
                                            '&{'.$template_name.':'.'probe.configvalue[RSM.IP'.$ipv.'.ENABLED]'.'.last(0)}=1',
			'priority' => '2',
                };

    create_trigger($options);

    $options = { 'description' => 'DNS-RTT-'.$proto_uc.' {HOST.NAME}: 5.1.1 Step 2 - AD bit is missing from '.$ns_name.' ['.$ip.']',
                         'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_DNS_RES_NOADBIT.
                                            '&{'.$template_name.':'.'probe.configvalue[RSM.IP'.$ipv.'.ENABLED]'.'.last(0)}=1',
			'priority' => '2',
                };

    create_trigger($options);

    return;
}

sub create_slv_item {
    my $name = shift;
    my $key = shift;
    my $hostid = shift;
    my $value_type = shift;
    my $applicationids = shift;

    my $options;
    if ($value_type == VALUE_TYPE_AVAIL)
    {
	$options = {'name' => $name,
                                              'key_'=> $key,
                                              'hostid' => $hostid,
                                              'type' => 2, 'value_type' => 3,
					      'applications' => $applicationids,
					      'valuemapid' => value_mappings->{'rsm_avail'}};
    }
    elsif ($value_type == VALUE_TYPE_PERC) {
	$options = {'name' => $name,
                                              'key_'=> $key,
                                              'hostid' => $hostid,
                                              'type' => 2, 'value_type' => 0,
                                              'applications' => $applicationids,
					      'units' => '%'};
    }
    else {
	pfail("Unknown value type $value_type.");
    }

    return create_item($options);
}

sub create_item_dns_udp_upd {
    my $ns_name = shift;
    my $ip = shift;
    my $templateid = shift;
    my $template_name = shift;

    my $proto_uc = 'UDP';

    my $options = {'name' => 'DNS update time of $2 ($3)',
                                              'key_'=> 'rsm.dns.udp.upd[{$RSM.TLD},'.$ns_name.','.$ip.']',
                                              'hostid' => $templateid,
                                              'applications' => [get_application_id('DNS RTT ('.$proto_uc.')', $templateid)],
                                              'type' => 2, 'value_type' => 0,
                                              'valuemapid' => value_mappings->{'rsm_dns'},
		                              'status' => (defined($OPTS{'epp-servers'}) ? 0 : 1)};
    return create_item($options);
}

sub create_items_dns {
    my $templateid = shift;
    my $template_name = shift;

    my $proto = 'tcp';
    my $proto_uc = uc($proto);
    my $item_key = 'rsm.dns.'.$proto.'[{$RSM.TLD}]';

    my $options = {'name' => 'Number of working DNS Name Servers of $1 ('.$proto_uc.')',
                                              'key_'=> $item_key,
                                              'hostid' => $templateid,
                                              'applications' => [get_application_id('DNS ('.$proto_uc.')', $templateid)],
                                              'type' => 3, 'value_type' => 3,
                                              'delay' => $cfg_dns_tcp_delay};

    create_item($options);

    $options = { 'description' => 'DNS-'.$proto_uc.' {HOST.NAME}: 5.2.3 - Less than {$RSM.DNS.AVAIL.MINNS} NS servers have answered succesfully',
                         'expression' => '{'.$template_name.':'.$item_key.'.last(0)}<{$RSM.DNS.AVAIL.MINNS}',
			'priority' => '4',
                };

    create_trigger($options);

    $proto = 'udp';
    $proto_uc = uc($proto);
    $item_key = 'rsm.dns.'.$proto.'[{$RSM.TLD}]';

    $options = {'name' => 'Number of working DNS Name Servers of $1 ('.$proto_uc.')',
                                              'key_'=> $item_key,
                                              'hostid' => $templateid,
                                              'applications' => [get_application_id('DNS ('.$proto_uc.')', $templateid)],
                                              'type' => 3, 'value_type' => 3,
                                              'delay' => $cfg_dns_udp_delay, 'valuemapid' => value_mappings->{'rsm_dns'}};

    create_item($options);

    $options = { 'description' => 'DNS-'.$proto_uc.' {HOST.NAME}: 5.2.3 - Less than {$RSM.DNS.AVAIL.MINNS} NS servers have answered succesfully',
                         'expression' => '{'.$template_name.':'.$item_key.'.last(0)}<{$RSM.DNS.AVAIL.MINNS}',
			'priority' => '4',
                };

    create_trigger($options);
}

sub create_items_rdds {
    my $templateid = shift;
    my $template_name = shift;

    my $applicationid_43 = get_application_id('RDDS43', $templateid);
    my $applicationid_80 = get_application_id('RDDS80', $templateid);

    my $item_key = 'rsm.rdds.43.ip[{$RSM.TLD}]';

    my $options = {'name' => 'RDDS43 IP of $1',
                                              'key_'=> $item_key,
                                              'hostid' => $templateid,
                                              'applications' => [$applicationid_43],
                                              'type' => 2, 'value_type' => 1,
                                              'valuemapid' => value_mappings->{'rsm_rdds_rttudp'}};
    create_item($options);

    $item_key = 'rsm.rdds.43.rtt[{$RSM.TLD}]';

    $options = {'name' => 'RDDS43 RTT of $1',
                                              'key_'=> $item_key,
                                              'hostid' => $templateid,
                                              'applications' => [$applicationid_43],
                                              'type' => 2, 'value_type' => 0,
                                              'valuemapid' => value_mappings->{'rsm_rdds_rttudp'}};
    create_item($options);

    $options = { 'description' => 'RDDS43-RTT {HOST.NAME}: 6.1.1 Step 5 - No reply from the server',
                         'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_RDDS43_NOREPLY,
                        'priority' => '2',
                };

    create_trigger($options);

    $options = { 'description' => 'RDDS43-RTT {HOST.NAME}: 6.1.1 Step 5 - The server output does not contain "{$RSM.RDDS.NS.STRING}"',
                         'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_RDDS43_NONS,
                        'priority' => '2',
                };

    create_trigger($options);

    $options = { 'description' => 'RDDS43-RTT {HOST.NAME}: 6.1.1 Step 6 - UNIX timestamp is missing in reply from the server',
                         'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_RDDS43_NOTS,
                        'priority' => '2',
                };

    create_trigger($options);

    $options = { 'description' => 'RDDS43-RTT {HOST.NAME}: 6.1.1 Step 6 - Invalid UNIX timestamp in reply from the server',
                         'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_RDDS43_ERRTS,
                        'priority' => '2',
                };

    create_trigger($options);

    $options = { 'description' => 'RDDS43-RTT {HOST.NAME}: 6.1.1 Step 2 - Cannot resolve a host',
                         'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_RDDS_ERRRES,
                        'priority' => '2',
                };

    create_trigger($options);

    if (defined($OPTS{'epp-servers'})) {
	$item_key = 'rsm.rdds.43.upd[{$RSM.TLD}]';

	$options = {'name' => 'RDDS43 update time of $1',
		    'key_'=> $item_key,
		    'hostid' => $templateid,
		    'applications' => [$applicationid_43],
		    'type' => 2, 'value_type' => 0,
		    'valuemapid' => value_mappings->{'rsm_rdds_rttudp'},
		    'status' => 0};
	create_item($options);

	$options = { 'description' => 'RDDS43-UPD {HOST.NAME}: No UNIX timestamp',
                         'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_RDDS43_NOTS,
                        'priority' => '2',
                };

	create_trigger($options);
    }

    $item_key = 'rsm.rdds.80.ip[{$RSM.TLD}]';

    $options = {'name' => 'RDDS80 IP of $1',
                                              'key_'=> $item_key,
                                              'hostid' => $templateid,
                                              'applications' => [$applicationid_80],
                                              'type' => 2, 'value_type' => 1};
    create_item($options);

    $item_key = 'rsm.rdds.80.rtt[{$RSM.TLD}]';

    $options = {'name' => 'RDDS80 RTT of $1',
                                              'key_'=> $item_key,
                                              'hostid' => $templateid,
                                              'applications' => [$applicationid_80],
                                              'type' => 2, 'value_type' => 0,
                                              'valuemapid' => value_mappings->{'rsm_rdds_rttudp'}};
    create_item($options);

    $options = { 'description' => 'RDDS80-RTT {HOST.NAME}: 6.1.1 Step 5 - No reply from the server',
                         'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_RDDS80_NOREPLY,
                        'priority' => '2',
                };

    create_trigger($options);

    $options = { 'description' => 'RDDS80-RTT {HOST.NAME}: 6.1.1 Step 2 - Cannot resolve a host',
                         'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_RDDS_ERRRES,
                        'priority' => '2',
                };

    create_trigger($options);

    $options = { 'description' => 'RDDS80-RTT {HOST.NAME}: 6.1.1 Step 2 - Cannot get HTTP response code from the server',
                         'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_RDDS80_NOHTTPCODE,
                        'priority' => '2',
                };

    create_trigger($options);

    $options = { 'description' => 'RDDS80-RTT {HOST.NAME}: 6.1.1 Step 2 - Invalid HTTP response code from the server',
                         'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_RDDS80_EHTTPCODE,
                        'priority' => '2',
                };

    create_trigger($options);

    $item_key = 'rsm.rdds[{$RSM.TLD},"'.$OPTS{'rdds43-servers'}.'","'.$OPTS{'rdds80-servers'}.'"]';

    $options = {'name' => 'RDDS availability of $1',
                                              'key_'=> $item_key,
                                              'hostid' => $templateid,
                                              'applications' => [get_application_id('RDDS', $templateid)],
                                              'type' => 3, 'value_type' => 3,
					      'delay' => $cfg_rdds_delay,
                                              'valuemapid' => value_mappings->{'rsm_rdds_avail'}};
    create_item($options);
}

sub create_items_epp {
    my $templateid = shift;
    my $template_name = shift;

    my $applicationid = get_application_id('EPP', $templateid);

    my ($item_key, $options);

    $item_key = 'rsm.epp[{$RSM.TLD},"'.$OPTS{'epp-servers'}.'"]';

    $options = {'name' => 'EPP service availability at $1 ($2)',
		'key_'=> $item_key,
		'hostid' => $templateid,
		'applications' => [$applicationid],
		'type' => 3, 'value_type' => 3,
		'delay' => $cfg_epp_delay, 'valuemapid' => value_mappings->{'rsm_avail'}};

    create_item($options);

    $item_key = 'rsm.epp.ip[{$RSM.TLD}]';

    $options = {'name' => 'EPP IP of $1',
		'key_'=> $item_key,
		'hostid' => $templateid,
		'applications' => [$applicationid],
		'type' => 2, 'value_type' => 1};

    create_item($options);

    $item_key = 'rsm.epp.rtt[{$RSM.TLD},login]';

    $options = {'name' => 'EPP $2 command RTT of $1',
		'key_'=> $item_key,
		'hostid' => $templateid,
		'applications' => [$applicationid],
		'type' => 2, 'value_type' => 0,
		'valuemapid' => value_mappings->{'rsm_epp'}};

    create_item($options);

    $item_key = 'rsm.epp.rtt[{$RSM.TLD},update]';

    $options = {'name' => 'EPP $2 command RTT of $1',
		'key_'=> $item_key,
		'hostid' => $templateid,
		'applications' => [$applicationid],
		'type' => 2, 'value_type' => 0,
		'valuemapid' => value_mappings->{'rsm_epp'}};

    create_item($options);

    $item_key = 'rsm.epp.rtt[{$RSM.TLD},info]';

    $options = {'name' => 'EPP $2 command RTT of $1',
		'key_'=> $item_key,
		'hostid' => $templateid,
		'applications' => [$applicationid],
		'type' => 2, 'value_type' => 0,
		'valuemapid' => value_mappings->{'rsm_epp'}};

    create_item($options);

    $options = { 'description' => 'EPP-INFO {HOST.NAME}: 7.1.1 Step 2 - IP is missing',
                 'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_EPP_NO_IP,
                'priority' => '2',
    };

    create_trigger($options);

    $options = { 'description' => 'EPP-INFO {HOST.NAME}: 7.1.1 Step 4 - Cannot connect to the server',
                 'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_EPP_CONNECT,
                'priority' => '2',
    };

    create_trigger($options);

    $options = { 'description' => 'EPP-INFO {HOST.NAME}: 7.1.1 Step 5 - Invalid certificate or private key',
                 'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_EPP_CRYPT,
                'priority' => '2',
    };

    create_trigger($options);

    $options = { 'description' => 'EPP-INFO {HOST.NAME}: 7.1.1 Step 5 - First message timeout',
                 'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_EPP_FIRSTTO,
                'priority' => '2',
    };

    create_trigger($options);

    $options = { 'description' => 'EPP-INFO {HOST.NAME}: 7.1.1 Step 5 - First message is invalid',
                 'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_EPP_FIRSTINVAL,
                'priority' => '2',
    };

    create_trigger($options);

    $options = { 'description' => 'EPP-INFO {HOST.NAME}: 7.1.1 Step 6 - LOGIN command timeout',
                 'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_EPP_LOGINTO,
                'priority' => '2',
    };

    create_trigger($options);

    $options = { 'description' => 'EPP-INFO {HOST.NAME}: 7.1.1 Step 6 - Invalid reply to LOGIN command',
                 'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_EPP_LOGININVAL,
                'priority' => '2',
    };

    create_trigger($options);

    $options = { 'description' => 'EPP-INFO {HOST.NAME}: 7.1.1 Step 7 - UPDATE command timeout',
                 'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_EPP_UPDATETO,
                'priority' => '2',
    };

    create_trigger($options);

    $options = { 'description' => 'EPP-INFO {HOST.NAME}: 7.1.1 Step 7 - Invalid reply to UPDATE command',
                 'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_EPP_UPDATEINVAL,
                'priority' => '2',
    };

    create_trigger($options);

    $options = { 'description' => 'EPP-INFO {HOST.NAME}: 7.1.1 Step 7 - INFO command timeout',
                 'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_EPP_INFOTO,
                'priority' => '2',
    };

    create_trigger($options);

    $options = { 'description' => 'EPP-INFO {HOST.NAME}: 7.1.1 Step 7 - Invalid reply to INFO command',
                 'expression' => '{'.$template_name.':'.$item_key.'.last(0)}='.ZBX_EC_EPP_INFOINVAL,
                'priority' => '2',
    };

    create_trigger($options);
}

sub create_macro {
    my $name = shift;
    my $value = shift;
    my $templateid = shift;
    my $force_update = shift;

    my $result;

    if (defined($templateid)) {
	if ($zabbix->get('usermacro',{'countOutput' => 1, 'hostids' => $templateid, 'filter' => {'macro' => $name}})) {
	    $result = $zabbix->get('usermacro',{'output' => 'hostmacroid', 'hostids' => $templateid, 'filter' => {'macro' => $name}} );
    	    $result = $zabbix->update('usermacro',{'hostmacroid' => $result->{'hostmacroid'}, 'value' => $value}) if defined $result->{'hostmacroid'}
														     and defined($force_update);
	}
	else {
	    $result = $zabbix->create('usermacro',{'hostid' => $templateid, 'macro' => $name, 'value' => $value});
        }

	return $result->{'hostmacroids'}[0];
    }
    else {
	if ($zabbix->get('usermacro',{'countOutput' => 1, 'globalmacro' => 1, 'filter' => {'macro' => $name}})) {
            $result = $zabbix->get('usermacro',{'output' => 'globalmacroid', 'globalmacro' => 1, 'filter' => {'macro' => $name}} );
            $result = $zabbix->macro_global_update({'globalmacroid' => $result->{'globalmacroid'}, 'value' => $value}) if defined $result->{'globalmacroid'}
															and defined($force_update);
        }
        else {
            $result = $zabbix->macro_global_create({'macro' => $name, 'value' => $value});
        }

	return $result->{'globalmacroids'}[0];
    }

}

sub create_probe_status_template {
    my $probe_name = shift;
    my $child_templateid = shift;

    my $template_name = 'Template '.$probe_name.' Status';

    my $templateid = create_template($template_name, $child_templateid);

    my $options = {'name' => 'Probe status ($1)',
                                              'key_'=> 'rsm.probe.status[automatic,'.$root_servers_macros.']',
                                              'hostid' => $templateid,
                                              'applications' => [get_application_id('Probe status', $templateid)],
                                              'type' => 3, 'value_type' => 3, 'delay' => cfg_probe_status_delay,
                                              'valuemapid' => value_mappings->{'rsm_probe'}};

    create_item($options);

    $options = { 'description' => 'PROBE {HOST.NAME}: 8.3 - Probe has been disable more than {$IP.MAX.OFFLINE.MANUAL} hours ago',
                         'expression' => '{'.$template_name.':rsm.probe.status[manual].max({$IP.MAX.OFFLINE.MANUAL}h)}=0',
                        'priority' => '3',
                };

    create_trigger($options);


    $options = {'name' => 'Probe status ($1)',
                                              'key_'=> 'rsm.probe.status[manual]',
                                              'hostid' => $templateid,
                                              'applications' => [get_application_id('Probe status', $templateid)],
                                              'type' => 2, 'value_type' => 3,
                                              'valuemapid' => value_mappings->{'rsm_probe'}};

    create_item($options);

    $options = { 'description' => 'PROBE {HOST.NAME}: 8.2 - Probe has been disabled by tests',
                         'expression' => '{'.$template_name.':rsm.probe.status[automatic,"{$RSM.IP4.ROOTSERVERS1}","{$RSM.IP6.ROOTSERVERS1}"].last(0)}=0',
                        'priority' => '4',
                };

    create_trigger($options);



    return $templateid;
}

sub create_probe_template {
    my $root_name = shift;

    my $templateid = create_template('Template '.$root_name);

    create_macro('{$RSM.IP4.ENABLED}', '1', $templateid);
    create_macro('{$RSM.IP6.ENABLED}', '1', $templateid);
    create_macro('{$RSM.RESOLVER}', '127.0.0.1', $templateid);
    create_macro('{$RSM.RDDS.ENABLED}', '1', $templateid);
    create_macro('{$RSM.EPP.ENABLED}', '1', $templateid);

    return $templateid;
}

sub update_root_servers {
    my $content = LWP::UserAgent->new->get('http://www.internic.net/zones/named.root')->{'_content'};

    my $macro_value_v4;
    my $macro_value_v6;
    my $macros_v4;
    my $macros_v6;
    my $cnt_macros_v4 = 1;
    my $cnt_macros_v6 = 1;

    return unless defined $content;

    for my $str (split("\n", $content)) {
	if ($str=~/.+ROOT\-SERVERS.+\sA\s+(.+)$/ and $OPTS{'ipv4'} == 1) {
	    if (defined($macro_value_v4) and length($macro_value_v4.','.$1) > 255) {
                $macros_v4 = $macros_v4.','.'{$RSM.IP4.ROOTSERVERS'.$cnt_macros_v4.'}' if defined($macros_v4);
                $macros_v4 = '{$RSM.IP4.ROOTSERVERS'.$cnt_macros_v4.'}' unless defined $macros_v4;
                create_macro('{$RSM.IP4.ROOTSERVERS'.$cnt_macros_v4.'}', $macro_value_v4);
                undef $macro_value_v4;
                $cnt_macros_v4++;
            }

	    $macro_value_v4 = $macro_value_v4.','.$1 if defined($macro_value_v4);
	    $macro_value_v4 = $1 unless defined $macro_value_v4;
	}

	if ($str=~/.+ROOT\-SERVERS.+AAAA\s+(.+)$/ and $OPTS{'ipv6'} == 1) {
            if (defined($macro_value_v6) and length($macro_value_v6.','.$1) > 255) {
                $macros_v6 = $macros_v6.','.'{$RSM.IP6.ROOTSERVERS'.$cnt_macros_v6.'}' if defined($macros_v6);
                $macros_v6 = '{$RSM.IP6.ROOTSERVERS'.$cnt_macros_v6.'}' unless defined $macros_v6;
                create_macro('{$RSM.IP6.ROOTSERVERS'.$cnt_macros_v6.'}', $macro_value_v6);
                undef $macro_value_v6;
                $cnt_macros_v6++;
            }

            $macro_value_v6 = $macro_value_v6.','.$1 if defined($macro_value_v6);
            $macro_value_v6 = $1 unless defined $macro_value_v6;
        }
    }

    unless (defined($macros_v4)) {
	if (defined($macro_value_v4)) {
	    $macros_v4 = '{$RSM.IP4.ROOTSERVERS1}';
	    create_macro('{$RSM.IP4.ROOTSERVERS1}', $macro_value_v4);
	}
    }
    else {
	$macros_v4 = $macros_v4.','.'{$RSM.IP4.ROOTSERVERS'.$cnt_macros_v4.'}';
	create_macro('{$RSM.IP4.ROOTSERVERS'.$cnt_macros_v4.'}', $macro_value_v4);
    }

    unless (defined($macros_v6)) {
	if (defined($macro_value_v6)) {
            $macros_v6 = '{$RSM.IP6.ROOTSERVERS1}';
            create_macro('{$RSM.IP6.ROOTSERVERS1}', $macro_value_v6);
        }
    }
    else {
        $macros_v6 = $macros_v6.','.'{$RSM.IP6.ROOTSERVERS'.$cnt_macros_v6.'}';
        create_macro('{$RSM.IP6.ROOTSERVERS'.$cnt_macros_v6.'}', $macro_value_v6);
    }

    return '"'.$macros_v4.'","'.$macros_v6.'"' if defined $macros_v4 and defined $macros_v6;
    return '"'.$macros_v4.'"' if defined $macros_v4 and !defined $macros_v6;
    return ','.$macros_v6.'"' if !defined $macros_v4 and defined $macros_v6;

    return ',,';
}

sub trim
{
    $_[0] =~ s/^\s*//g;
    $_[0] =~ s/\s*$//g;
}

sub get_sensdata
{
    my $prompt = shift;

    my $sensdata;

    print($prompt);
    system('stty', '-echo');
    chop($sensdata = <STDIN>);
    system('stty', 'echo');
    print("\n");

    return $sensdata;
}

sub exp_get_keysalt
{
    my $self = shift;

    if ($self->match() =~ m/^([^\s]+\|[^\s]+)/)
    {
	$exp_output = $1;
    }
}

sub get_encrypted_passwd
{
    my $keysalt = shift;
    my $passphrase = shift;
    my $passwd = shift;

    my @params = split('\|', $keysalt);

    pfail("$keysalt: invalid keysalt") unless (scalar(@params) == 2);

    push(@params, '-n');

    my $exp = new Expect or pfail("cannot create Expect object");
    $exp->raw_pty(1);
    $exp->spawn($exp_command, @params) or pfail("cannot spawn $exp_command: $!");

    $exp->send("$passphrase\n");
    $exp->send("$passwd\n");

    print("");
    $exp->expect($exp_timeout, [qr/.*\n/, \&exp_get_keysalt]);

    $exp->soft_close();

    pfail("$exp_command returned error") unless ($exp_output and $exp_output =~ m/\|/);

    my $ret = $exp_output;
    $exp_output = undef;

    return $ret;
}

sub get_encrypted_privkey
{
    my $keysalt = shift;
    my $passphrase = shift;
    my $file = shift;

    my @params = split('\|', $keysalt);

    pfail("$keysalt: invalid keysalt") unless (scalar(@params) == 2);

    push(@params, '-n', '-f', $file);

    my $exp = new Expect or pfail("cannot create Expect object");
    $exp->raw_pty(1);
    $exp->spawn($exp_command, @params) or pfail("cannot spawn $exp_command: $!");

    $exp->send("$passphrase\n");

    print("");
    $exp->expect($exp_timeout, [qr/.*\n/, \&exp_get_keysalt]);

    $exp->soft_close();

    pfail("$exp_command returned error") unless ($exp_output and $exp_output =~ m/\|/);

    my $ret = $exp_output;
    $exp_output = undef;

    return $ret;
}

sub read_file {
    my $file = shift;

    my $contents = do {
	local $/ = undef;
	open my $fh, "<", $file or pfail("could not open $file: $!");
	<$fh>;
    };

    return $contents;
}

sub get_md5 {
    my $file = shift;

    my $contents = do {
        local $/ = undef;
        open(my $fh, "<", $file) or pfail("cannot open $file: $!");
        <$fh>;
    };

    my $index = index($contents, "-----BEGIN CERTIFICATE-----");
    pfail("specified file $file does not contain line \"-----BEGIN CERTIFICATE-----\"") if ($index == -1);

    return md5_hex(substr($contents, $index));
}

sub create_main_template {
    my $tld = shift;
    my $ns_servers = shift;

    my $template_name = 'Template '.$tld;

    my $templateid = create_template($template_name);

    pfail("cannot create Template '".$template_name."'") unless ($templateid);

    my $delay = 300;
    my $appid = get_application_id('Configuration', $templateid);
    my ($options, $key);

    foreach my $m ('RSM.IP4.ENABLED', 'RSM.IP6.ENABLED') {
        $key = 'probe.configvalue['.$m.']';

        $options = {'name' => 'Value of $1 variable',
                    'key_'=> $key,
                    'hostid' => $templateid,
                    'applications' => [$appid],
                    'params' => '{$'.$m.'}',
                    'delay' => $delay,
                    'type' => 15, 'value_type' => 3};

        create_item($options);
    }

    foreach my $ns_name (sort keys %{$ns_servers}) {
	print $ns_name."\n";

        my @ipv4 = defined(@{$ns_servers->{$ns_name}{'v4'}}) ? @{$ns_servers->{$ns_name}{'v4'}} : undef;
	my @ipv6 = defined(@{$ns_servers->{$ns_name}{'v6'}}) ? @{$ns_servers->{$ns_name}{'v6'}} : undef;

        foreach (my $i_ipv4 = 0; $i_ipv4 <= $#ipv4; $i_ipv4++) {
	    next unless defined $ipv4[$i_ipv4];
	    print "	--v4     $ipv4[$i_ipv4]\n";

            create_item_dns_rtt($ns_name, $ipv4[$i_ipv4], $templateid, $template_name, "tcp", '4');
	    create_item_dns_rtt($ns_name, $ipv4[$i_ipv4], $templateid, $template_name, "udp", '4');
	    if (defined($OPTS{'epp-servers'})) {
    		create_item_dns_udp_upd($ns_name, $ipv4[$i_ipv4], $templateid);

		my $options = { 'description' => 'DNS-UPD-UDP {HOST.NAME}: No UNIX timestamp for ['.$ipv4[$i_ipv4].']',
            	             'expression' => '{'.$template_name.':'.'rsm.dns.udp.upd[{$RSM.TLD},'.$ns_name.','.$ipv4[$i_ipv4].']'.'.last(0)}='.ZBX_EC_DNS_NS_NOTS.
					     '&{'.$template_name.':'.'probe.configvalue[RSM.IP4.ENABLED]'.'.last(0)}=1',
                	    'priority' => '2',
                };

	        create_trigger($options);
    	    }
        }

	foreach (my $i_ipv6 = 0; $i_ipv6 <= $#ipv6; $i_ipv6++) {
	    next unless defined $ipv6[$i_ipv6];
    	    print "	--v6     $ipv6[$i_ipv6]\n";

	    create_item_dns_rtt($ns_name, $ipv6[$i_ipv6], $templateid, $template_name, "tcp", '6');
    	    create_item_dns_rtt($ns_name, $ipv6[$i_ipv6], $templateid, $template_name, "udp", '6');
	    if (defined($OPTS{'epp-servers'})) {
    		create_item_dns_udp_upd($ns_name, $ipv6[$i_ipv6], $templateid);

		my $options = { 'description' => 'DNS-UPD-UDP {HOST.NAME}: No UNIX timestamp for ['.$ipv6[$i_ipv6].']',
                             'expression' => '{'.$template_name.':'.'rsm.dns.udp.upd[{$RSM.TLD},'.$ns_name.','.$ipv6[$i_ipv6].']'.'.last(0)}='.ZBX_EC_DNS_NS_NOTS.
						'&{'.$template_name.':'.'probe.configvalue[RSM.IP6.ENABLED]'.'.last(0)}=1',
                            'priority' => '2',
                };

                create_trigger($options);
	    }
        }
    }

    create_items_dns($templateid, $template_name);
    create_items_rdds($templateid, $template_name) if (defined($OPTS{'rdds43-servers'}));
    create_items_epp($templateid, $template_name) if (defined($OPTS{'epp-servers'}));

    create_macro('{$RSM.TLD}', $tld, $templateid);
    create_macro('{$RSM.DNS.TESTPREFIX}', $OPTS{'dns-test-prefix'}, $templateid);
    create_macro('{$RSM.RDDS.TESTPREFIX}', $OPTS{'rdds-test-prefix'}, $templateid) if (defined($OPTS{'rdds-test-prefix'}));
    create_macro('{$RSM.RDDS.NS.STRING}', defined($OPTS{'rdds-ns-string'}) ? $OPTS{'rdds-ns-string'} : cfg_default_rdds_ns_string, $templateid);
    create_macro('{$RSM.TLD.DNSSEC.ENABLED}', defined($OPTS{'dnssec'}) ? 1 : 0, $templateid);
    create_macro('{$RSM.TLD.RDDS.ENABLED}', defined($OPTS{'rdds43-servers'}) ? 1 : 0, $templateid);
    create_macro('{$RSM.TLD.EPP.ENABLED}', defined($OPTS{'epp-servers'}) ? 1 : 0, $templateid);

    if ($OPTS{'epp-servers'})
    {
	my $m = '{$RSM.EPP.KEYSALT}';
	unless (($result = $zabbix->get('usermacro', {'globalmacro' => 1, output => 'extend', 'filter' => {'macro' => $m}}))
		and defined $result->{'value'}) {
	    pfail('cannot get macro ', $m);
	}
	my $keysalt = $result->{'value'};
	trim($keysalt);
	pfail("global macro $m must conatin |") unless ($keysalt =~ m/\|/);

	if ($OPTS{'epp-commands'}) {
	    create_macro('{$RSM.EPP.COMMANDS}', $OPTS{'epp-commands'}, $templateid, 1);
	} else {
	    create_macro('{$RSM.EPP.COMMANDS}', '/opt/epp/'.$tld.'/commands', $templateid);
	}
	create_macro('{$RSM.EPP.USER}', $OPTS{'epp-user'}, $templateid, 1);
	create_macro('{$RSM.EPP.CERT}', encode_base64(read_file($OPTS{'epp-cert'}), ''),  $templateid, 1);
	create_macro('{$RSM.EPP.SERVERID}', $OPTS{'epp-serverid'}, $templateid, 1);
	create_macro('{$RSM.EPP.TESTPREFIX}', $OPTS{'epp-test-prefix'}, $templateid, 1);
	create_macro('{$RSM.EPP.SERVERCERTMD5}', get_md5($OPTS{'epp-servercert'}), $templateid, 1);

	my $passphrase = get_sensdata("Enter EPP secret key passphrase: ");
	my $passwd = get_sensdata("Enter EPP password: ");
	create_macro('{$RSM.EPP.PASSWD}', get_encrypted_passwd($keysalt, $passphrase, $passwd), $templateid, 1);
	$passwd = undef;
	create_macro('{$RSM.EPP.PRIVKEY}', get_encrypted_privkey($keysalt, $passphrase, $OPTS{'epp-privkey'}), $templateid, 1);
	$passphrase = undef;

	print("EPP data saved successfully.\n");
    }

    return $templateid;
}

sub create_all_slv_ns_items {
    my $ns_name = shift;
    my $ip = shift;
    my $hostid = shift;

    create_slv_item('% of successful monthly DNS resolution RTT (UDP): $1 ($2)', 'rsm.slv.dns.ns.rtt.udp.month['.$ns_name.','.$ip.']', $hostid, VALUE_TYPE_PERC, [get_application_id(APP_SLV_MONTHLY, $hostid)]);
    create_slv_item('% of successful monthly DNS resolution RTT (TCP): $1 ($2)', 'rsm.slv.dns.ns.rtt.tcp.month['.$ns_name.','.$ip.']', $hostid, VALUE_TYPE_PERC, [get_application_id(APP_SLV_MONTHLY, $hostid)]);
    create_slv_item('% of successful monthly DNS update time: $1 ($2)', 'rsm.slv.dns.ns.upd.month['.$ns_name.','.$ip.']', $hostid, VALUE_TYPE_PERC, [get_application_id(APP_SLV_MONTHLY, $hostid)]) if (defined($OPTS{'epp-servers'}));
    create_slv_item('DNS NS availability: $1 ($2)', 'rsm.slv.dns.ns.avail['.$ns_name.','.$ip.']', $hostid, VALUE_TYPE_AVAIL, [get_application_id(APP_SLV_PARTTEST, $hostid)]);
    create_slv_item('% of monthly DNS NS availability: $1 ($2)', 'rsm.slv.dns.ns.month['.$ns_name.','.$ip.']', $hostid, VALUE_TYPE_PERC, [get_application_id(APP_SLV_MONTHLY, $hostid)]);
}

sub create_slv_ns_items {
    my $ns_servers = shift;
    my $hostid = shift;

    foreach my $ns_name (sort keys %{$ns_servers}) {
        my @ipv4 = defined(@{$ns_servers->{$ns_name}{'v4'}}) ? @{$ns_servers->{$ns_name}{'v4'}} : undef;
	my @ipv6 = defined(@{$ns_servers->{$ns_name}{'v6'}}) ? @{$ns_servers->{$ns_name}{'v6'}} : undef;

        foreach (my $i_ipv4 = 0; $i_ipv4 <= $#ipv4; $i_ipv4++) {
	    next unless defined $ipv4[$i_ipv4];

	    create_all_slv_ns_items($ns_name, $ipv4[$i_ipv4], $hostid);
        }

	foreach (my $i_ipv6 = 0; $i_ipv6 <= $#ipv6; $i_ipv6++) {
	    next unless defined $ipv6[$i_ipv6];

	    create_all_slv_ns_items($ns_name, $ipv6[$i_ipv6], $hostid);
        }
    }
}

sub create_slv_items {
    my $ns_servers = shift;
    my $hostid = shift;
    my $host_name = shift;

    create_slv_ns_items($ns_servers, $hostid);

    create_slv_item('DNS availability', 'rsm.slv.dns.avail', $hostid, VALUE_TYPE_AVAIL, [get_application_id(APP_SLV_PARTTEST, $hostid)]);

    my $options;

    # NB! Configuration trigger that is used in PHP and C code to detect incident!
    # priority must be set to 0!
    $options = { 'description' => 'DNS-AVAIL {HOST.NAME}: 5.2.4 - The service is not available',
                         'expression' => '({TRIGGER.VALUE}=0&'.
						'{'.$host_name.':rsm.slv.dns.avail.count(#{$RSM.INCIDENT.DNS.FAIL},0,"eq")}={$RSM.INCIDENT.DNS.FAIL})|'.
					 '({TRIGGER.VALUE}=1&'.
						'{'.$host_name.':rsm.slv.dns.avail.count(#{$RSM.INCIDENT.DNS.RECOVER},0,"ne")}<{$RSM.INCIDENT.DNS.RECOVER})',
                        'priority' => '0',
                };

    create_trigger($options);

    create_slv_item('DNS weekly unavailability', 'rsm.slv.dns.rollweek', $hostid, VALUE_TYPE_PERC, [get_application_id(APP_SLV_ROLLWEEK, $hostid)]);

    $options = { 'description' => 'DNS-ROLLWEEK {HOST.NAME}: 5.2.5 - The Service Availability [{ITEM.LASTVALUE1}] > 10%',
                         'expression' => '({'.$host_name.':rsm.slv.dns.rollweek.last(0)}=10|'.
					'{'.$host_name.':rsm.slv.dns.rollweek.last(0)}>10)&'.
					'{'.$host_name.':rsm.slv.dns.rollweek.last(0)}<25',
                        'priority' => '2',
                };

    create_trigger($options);

    $options = { 'description' => 'DNS-ROLLWEEK {HOST.NAME}: 5.2.5 - The Service Availability [{ITEM.LASTVALUE1}] > 25%',
                         'expression' => '({'.$host_name.':rsm.slv.dns.rollweek.last(0)}=25|'.
					'{'.$host_name.':rsm.slv.dns.rollweek.last(0)}>25)&'.
                                        '{'.$host_name.':rsm.slv.dns.rollweek.last(0)}<50',
                        'priority' => '3',
                };

    create_trigger($options);

    $options = { 'description' => 'DNS-ROLLWEEK {HOST.NAME}: 5.2.5 - The Service Availability [{ITEM.LASTVALUE1}] > 50%',
                         'expression' => '({'.$host_name.':rsm.slv.dns.rollweek.last(0)}=50|'.
					'{'.$host_name.':rsm.slv.dns.rollweek.last(0)}>50)&'.
                                        '{'.$host_name.':rsm.slv.dns.rollweek.last(0)}<75',
                        'priority' => '3',
                };

    create_trigger($options);

    $options = { 'description' => 'DNS-ROLLWEEK {HOST.NAME}: 5.2.5 - The Service Availability [{ITEM.LASTVALUE1}] > 75%',
                         'expression' => '({'.$host_name.':rsm.slv.dns.rollweek.last(0)}>75|'.
					'{'.$host_name.':rsm.slv.dns.rollweek.last(0)}>75)&'.
                                        '{'.$host_name.':rsm.slv.dns.rollweek.last(0)}<90',
                        'priority' => '4',
                };

    create_trigger($options);

    $options = { 'description' => 'DNS-ROLLWEEK {HOST.NAME}: 5.2.5 - The Service Availability [{ITEM.LASTVALUE1}] > 90%',
                         'expression' => '({'.$host_name.':rsm.slv.dns.rollweek.last(0)}=90|'.
					'{'.$host_name.':rsm.slv.dns.rollweek.last(0)}>90)&'.
                                        '{'.$host_name.':rsm.slv.dns.rollweek.last(0)}<100',
                        'priority' => '4',
                };

    create_trigger($options);

    $options = { 'description' => 'DNS-ROLLWEEK {HOST.NAME}: 5.2.5 - The Service Availability [{ITEM.LASTVALUE1}] > 100%',
                         'expression' => '{'.$host_name.':rsm.slv.dns.rollweek.last(0)}=100|'.
					'{'.$host_name.':rsm.slv.dns.rollweek.last(0)}>100',
                        'priority' => '5',
                };

    create_trigger($options);

    if (defined($OPTS{'dnssec'})) {
	create_slv_item('DNSSEC availability', 'rsm.slv.dnssec.avail', $hostid, VALUE_TYPE_AVAIL, [get_application_id(APP_SLV_PARTTEST, $hostid)]);

	# NB! Configuration trigger that is used in PHP and C code to detect incident!
	# priority must be set to 0!
	$options = { 'description' => 'DNSSEC-AVAIL {HOST.NAME}: 5.3.3 - The service is not available',
		     'expression' => '({TRIGGER.VALUE}=0&'.
			 '{'.$host_name.':rsm.slv.dnssec.avail.count(#{$RSM.INCIDENT.DNSSEC.FAIL},0,"eq")}={$RSM.INCIDENT.DNSSEC.FAIL})|'.
			 '({TRIGGER.VALUE}=1&'.
			 '{'.$host_name.':rsm.slv.dnssec.avail.count(#{$RSM.INCIDENT.DNSSEC.RECOVER},0,"ne")}<{$RSM.INCIDENT.DNSSEC.RECOVER})',
			 'priority' => '0',
	};

	create_trigger($options);

	create_slv_item('DNSSEC weekly unavailability', 'rsm.slv.dnssec.rollweek', $hostid, VALUE_TYPE_PERC, [get_application_id(APP_SLV_ROLLWEEK, $hostid)]);

	$options = { 'description' => 'DNSSEC-ROLLWEEK {HOST.NAME}: 5.3.4 - Proper resolution [{ITEM.LASTVALUE1}] > 10%',
		     'expression' => '{'.$host_name.':rsm.slv.dnssec.rollweek.last(0)}>10&'.
			 '{'.$host_name.':rsm.slv.dnssec.rollweek.last(0)}<25',
			 'priority' => '2',
	};

	create_trigger($options);

	$options = { 'description' => 'DNSSEC-ROLLWEEK {HOST.NAME}: 5.3.4 - Proper resolution [{ITEM.LASTVALUE1}] > 25%',
		     'expression' => '{'.$host_name.':rsm.slv.dnssec.rollweek.last(0)}>25&'.
			 '{'.$host_name.':rsm.slv.dnssec.rollweek.last(0)}<50',
			 'priority' => '3',
	};

	create_trigger($options);

	$options = { 'description' => 'DNSSEC-ROLLWEEK {HOST.NAME}: 5.3.4 - Proper resolution [{ITEM.LASTVALUE1}] > 50%',
		     'expression' => '{'.$host_name.':rsm.slv.dnssec.rollweek.last(0)}>50&'.
			 '{'.$host_name.':rsm.slv.dnssec.rollweek.last(0)}<75',
			 'priority' => '3',
	};

	create_trigger($options);

	$options = { 'description' => 'DNSSEC-ROLLWEEK {HOST.NAME}: 5.3.4 - Proper resolution [{ITEM.LASTVALUE1}] > 75%',
		     'expression' => '{'.$host_name.':rsm.slv.dnssec.rollweek.last(0)}>75&'.
			 '{'.$host_name.':rsm.slv.dnssec.rollweek.last(0)}<90',
			 'priority' => '4',
	};

	create_trigger($options);

	$options = { 'description' => 'DNSSEC-ROLLWEEK {HOST.NAME}: 5.3.4 - Proper resolution [{ITEM.LASTVALUE1}] > 90%',
		     'expression' => '{'.$host_name.':rsm.slv.dnssec.rollweek.last(0)}>90&'.
			 '{'.$host_name.':rsm.slv.dnssec.rollweek.last(0)}<100',
			 'priority' => '4',
	};

	create_trigger($options);

	$options = { 'description' => 'DNSSEC-ROLLWEEK {HOST.NAME}: 5.3.4 - Proper resolution [{ITEM.LASTVALUE1}] > 100%',
		     'expression' => '{'.$host_name.':rsm.slv.dnssec.rollweek.last(0)}>100',
		     'priority' => '5',
	};

	create_trigger($options);
    }

    if (defined($OPTS{'rdds43-servers'})) {
	create_slv_item('RDDS availability', 'rsm.slv.rdds.avail', $hostid, VALUE_TYPE_AVAIL, [get_application_id(APP_SLV_PARTTEST, $hostid)]);

	# NB! Configuration trigger that is used in PHP and C code to detect incident!
	# priority must be set to 0!
	$options = { 'description' => 'RDDS-AVAIL {HOST.NAME}: 6.2.3 - The service is not available',
		     'expression' => '({TRIGGER.VALUE}=0&'.
			 '{'.$host_name.':rsm.slv.rdds.avail.count(#{$RSM.INCIDENT.RDDS.FAIL},0,"eq")}={$RSM.INCIDENT.RDDS.FAIL})|'.
			 '({TRIGGER.VALUE}=1&'.
			 '{'.$host_name.':rsm.slv.rdds.avail.count(#{$RSM.INCIDENT.RDDS.RECOVER},0,"ne")}<{$RSM.INCIDENT.RDDS.RECOVER})',
			 'priority' => '0',
	};

	create_trigger($options);

	create_slv_item('RDDS weekly unavailability', 'rsm.slv.rdds.rollweek', $hostid, VALUE_TYPE_PERC, [get_application_id(APP_SLV_ROLLWEEK, $hostid)]);

        $options = { 'description' => 'RDDS-ROLLWEEK {HOST.NAME}: 6.2.4 - The Service Availability [{ITEM.LASTVALUE1}] > 10%',
		     'expression' => '{'.$host_name.':rsm.slv.rdds.rollweek.last(0)}>10&'.
			 '{'.$host_name.':rsm.slv.rdds.rollweek.last(0)}<25',
			 'priority' => '2',
	};

	create_trigger($options);

	$options = { 'description' => 'RDDS-ROLLWEEK {HOST.NAME}: 6.2.4 - The Service Availability [{ITEM.LASTVALUE1}] > 25%',
		     'expression' => '{'.$host_name.':rsm.slv.rdds.rollweek.last(0)}>25&'.
			 '{'.$host_name.':rsm.slv.rdds.rollweek.last(0)}<50',
			 'priority' => '3',
	};

	create_trigger($options);

	$options = { 'description' => 'RDDS-ROLLWEEK {HOST.NAME}: 6.2.4 - The Service Availability [{ITEM.LASTVALUE1}] > 50%',
		     'expression' => '{'.$host_name.':rsm.slv.rdds.rollweek.last(0)}>50&'.
			 '{'.$host_name.':rsm.slv.rdds.rollweek.last(0)}<75',
			 'priority' => '3',
	};

	create_trigger($options);

	$options = { 'description' => 'RDDS-ROLLWEEK {HOST.NAME}: 6.2.4 - The Service Availability [{ITEM.LASTVALUE1}] > 75%',
		     'expression' => '{'.$host_name.':rsm.slv.rdds.rollweek.last(0)}>75&'.
			 '{'.$host_name.':rsm.slv.rdds.rollweek.last(0)}<90',
			 'priority' => '4',
	};

	create_trigger($options);

	$options = { 'description' => 'RDDS-ROLLWEEK {HOST.NAME}: 6.2.4 - The Service Availability [{ITEM.LASTVALUE1}] > 90%',
		     'expression' => '{'.$host_name.':rsm.slv.rdds.rollweek.last(0)}>90&'.
			 '{'.$host_name.':rsm.slv.rdds.rollweek.last(0)}<100',
			 'priority' => '4',
	};

	create_trigger($options);

	$options = { 'description' => 'RDDS-ROLLWEEK {HOST.NAME}: 6.2.4 - The Service Availability [{ITEM.LASTVALUE1}] > 100%',
		     'expression' => '{'.$host_name.':rsm.slv.rdds.rollweek.last(0)}>100',
		     'priority' => '5',
	};

	create_trigger($options);

	create_slv_item('% of successful monthly RDDS43 resolution RTT', 'rsm.slv.rdds.43.rtt.month', $hostid, VALUE_TYPE_PERC, [get_application_id(APP_SLV_MONTHLY, $hostid)]);
	create_slv_item('% of successful monthly RDDS80 resolution RTT', 'rsm.slv.rdds.80.rtt.month', $hostid, VALUE_TYPE_PERC, [get_application_id(APP_SLV_MONTHLY, $hostid)]);
	create_slv_item('% of successful monthly RDDS update time', 'rsm.slv.rdds.upd.month', $hostid, VALUE_TYPE_PERC, [get_application_id(APP_SLV_MONTHLY, $hostid)]) if (defined($OPTS{'epp-servers'}));
    }

    if (defined($OPTS{'epp-servers'})) {
	create_slv_item('EPP availability', 'rsm.slv.epp.avail', $hostid, VALUE_TYPE_AVAIL, [get_application_id(APP_SLV_PARTTEST, $hostid)]);
	create_slv_item('EPP weekly unavailability', 'rsm.slv.epp.rollweek', $hostid, VALUE_TYPE_PERC, [get_application_id(APP_SLV_ROLLWEEK, $hostid)]);

	create_slv_item('% of successful monthly EPP LOGIN resolution RTT', 'rsm.slv.epp.rtt.login.month', $hostid, VALUE_TYPE_PERC, [get_application_id(APP_SLV_MONTHLY, $hostid)]);
	create_slv_item('% of successful monthly EPP UPDATE resolution RTT', 'rsm.slv.epp.rtt.update.month', $hostid, VALUE_TYPE_PERC, [get_application_id(APP_SLV_MONTHLY, $hostid)]);
	create_slv_item('% of successful monthly EPP INFO resolution RTT', 'rsm.slv.epp.rtt.info.month', $hostid, VALUE_TYPE_PERC, [get_application_id(APP_SLV_MONTHLY, $hostid)]);

	# NB! Configuration trigger that is used in PHP and C code to detect incident!
	# priority must be set to 0!
	$options = { 'description' => 'EPP-AVAIL {HOST.NAME}: 7.2.3 - The service is not available',
		     'expression' => '({TRIGGER.VALUE}=0&'.
			 '{'.$host_name.':rsm.slv.epp.avail.count(#{$RSM.INCIDENT.EPP.FAIL},0,"eq")}={$RSM.INCIDENT.EPP.FAIL})|'.
			 '({TRIGGER.VALUE}=1&'.
			 '{'.$host_name.':rsm.slv.epp.avail.count(#{$RSM.INCIDENT.EPP.RECOVER},0,"ne")}<{$RSM.INCIDENT.EPP.RECOVER})',
			 'priority' => '0',
	};

	create_trigger($options);

        $options = { 'description' => 'EPP-ROLLWEEK {HOST.NAME}: 7.2.4 - The Service Availability [{ITEM.LASTVALUE1}] > 10%',
                         'expression' => '({'.$host_name.':rsm.slv.epp.rollweek.last(0)}=10|'.
                                        '{'.$host_name.':rsm.slv.epp.rollweek.last(0)}>10)&'.
                                        '{'.$host_name.':rsm.slv.epp.rollweek.last(0)}<25',
                        'priority' => '2',
                };

	create_trigger($options);

	$options = { 'description' => 'EPP-ROLLWEEK {HOST.NAME}: 7.2.4 - The Service Availability [{ITEM.LASTVALUE1}] > 25%',
                         'expression' => '({'.$host_name.':rsm.slv.epp.rollweek.last(0)}=25|'.
                                        '{'.$host_name.':rsm.slv.epp.rollweek.last(0)}>25)&'.
                                        '{'.$host_name.':rsm.slv.epp.rollweek.last(0)}<50',
                        'priority' => '3',
                };

	create_trigger($options);

	$options = { 'description' => 'EPP-ROLLWEEK {HOST.NAME}: 7.2.4 - The Service Availability [{ITEM.LASTVALUE1}] > 50%',
                         'expression' => '({'.$host_name.':rsm.slv.epp.rollweek.last(0)}=50|'.
                                        '{'.$host_name.':rsm.slv.epp.rollweek.last(0)}>50)&'.
                                        '{'.$host_name.':rsm.slv.epp.rollweek.last(0)}<75',
                        'priority' => '3',
                };

	create_trigger($options);

	$options = { 'description' => 'EPP-ROLLWEEK {HOST.NAME}: 7.2.4 - The Service Availability [{ITEM.LASTVALUE1}] > 75%',
                         'expression' => '({'.$host_name.':rsm.slv.epp.rollweek.last(0)}>75|'.
                                        '{'.$host_name.':rsm.slv.epp.rollweek.last(0)}>75)&'.
                                        '{'.$host_name.':rsm.slv.epp.rollweek.last(0)}<90',
                        'priority' => '4',
                };

	create_trigger($options);

	$options = { 'description' => 'EPP-ROLLWEEK {HOST.NAME}: 7.2.4 - The Service Availability [{ITEM.LASTVALUE1}] > 90%',
                         'expression' => '({'.$host_name.':rsm.slv.epp.rollweek.last(0)}=90|'.
                                        '{'.$host_name.':rsm.slv.epp.rollweek.last(0)}>90)&'.
                                        '{'.$host_name.':rsm.slv.epp.rollweek.last(0)}<100',
                        'priority' => '4',
                };

	create_trigger($options);

        $options = { 'description' => 'EPP-ROLLWEEK {HOST.NAME}: 7.2.4 - The Service Availability [{ITEM.LASTVALUE1}] > 100%',
                         'expression' => '{'.$host_name.':rsm.slv.epp.rollweek.last(0)}=100|'.
                                        '{'.$host_name.':rsm.slv.epp.rollweek.last(0)}>100',
                        'priority' => '5',
                };

	create_trigger($options);

    }
}

# calculated items, configuration history (TODO: rename host to something like config_history)
sub create_rsm_items {
    my $hostid = shift;

    my $options;
    my $appid = get_application_id('Configuration', $hostid);

    my $delay = 60; # every minute
    foreach my $m (
	'RSM.INCIDENT.DNS.FAIL',
	'RSM.INCIDENT.DNS.RECOVER',
	'RSM.INCIDENT.DNSSEC.FAIL',
	'RSM.INCIDENT.DNSSEC.RECOVER',
	'RSM.INCIDENT.RDDS.FAIL',
	'RSM.INCIDENT.RDDS.RECOVER',
	'RSM.INCIDENT.EPP.FAIL',
	'RSM.INCIDENT.EPP.RECOVER',
	'RSM.DNS.UDP.DELAY',
	'RSM.RDDS.DELAY',
	'RSM.EPP.DELAY',
	'RSM.DNS.UDP.RTT.HIGH',
	'RSM.DNS.AVAIL.MINNS',
	'RSM.DNS.ROLLWEEK.SLA',
	'RSM.RDDS.ROLLWEEK.SLA',
	'RSM.EPP.ROLLWEEK.SLA')
    {
	$options = {'name' => '$1 value',
		   'key_'=> 'rsm.configvalue['.$m.']',
		   'hostid' => $hostid,
		   'applications' => [$appid],
		   'params' => '{$'.$m.'}',
		   'delay' => $delay,
		   'type' => 15, 'value_type' => 3};

	create_item($options);
    }

    $delay = 60 * 60 * 24; # every day
    foreach my $m (
	'RSM.SLV.DNS.UDP.RTT',
	'RSM.SLV.DNS.TCP.RTT',
	'RSM.SLV.NS.AVAIL',
	'RSM.SLV.RDDS43.RTT',
	'RSM.SLV.RDDS80.RTT',
	'RSM.SLV.RDDS.UPD',
	'RSM.SLV.DNS.NS.UPD',
	'RSM.SLV.EPP.LOGIN',
	'RSM.SLV.EPP.UPDATE',
	'RSM.SLV.EPP.INFO')
    {
	$options = {'name' => '$1 value',
		   'key_'=> 'rsm.configvalue['.$m.']',
		   'hostid' => $hostid,
		   'applications' => [$appid],
		   'params' => '{$'.$m.'}',
		   'delay' => $delay,
		   'type' => 15, 'value_type' => 3};

	create_item($options);
    }
}

sub pfail {
    print("Error: ", @_, "\n");
    exit -1;
}

sub create_cron_items {
    my $slv_path = $config->{'slv'}->{'path'};

    my $rv = opendir DIR, $slv_path;

    pfail("cannot open $slv_path") unless ($rv);

    my $avail_shift = 30;
    my $rollweek_shift = 0;

    my $slv_file;
    while (($slv_file = readdir DIR)) {
	next unless ($slv_file =~ /^rsm\.slv\..*\.pl$/);

	if ($slv_file =~ /\.slv\..*\.month\.pl$/) {
	    # check monthly data once a day
	    system("echo '0 0 * * * root $slv_path/$slv_file' > /etc/cron.d/$slv_file");
	} elsif ($slv_file =~ /\.slv\..*\.avail\.pl$/) {
	    # separate rollweek and avail by some delay
	    system("echo '* * * * * root sleep $avail_shift; $slv_path/$slv_file' > /etc/cron.d/$slv_file");
	    $avail_shift += 5;
	    $avail_shift = 30 unless ($avail_shift < 60);
	} else {
	    system("echo '* * * * * root sleep $rollweek_shift; $slv_path/$slv_file' > /etc/cron.d/$slv_file");
	    $rollweek_shift += 5;
	    $rollweek_shift = 0 unless ($rollweek_shift < 30);
	}
    }
}

sub usage {
    my ($opt_name, $opt_value) = @_;

    my $cfg_default_rdds_ns_string = cfg_default_rdds_ns_string;

    print <<EOF;

    Usage: $0 [options]

Required options

        --tld=STRING
                TLD name
        --dns-test-prefix=STRING
                domain test prefix for DNS monitoring (specify '*randomtld*' for root servers monitoring)

Other options

        --ipv4
                enable IPv4
		(default: disabled)
        --ipv6
                enable IPv6
		(default: disabled)
        --dnssec
                enable DNSSEC in DNS tests
		(default: disabled)
        --ns-servers-v4=STRING
                list of IPv4 name servers separated by space (name and IP separated by comma): "NAME,IP[ NAME,IP2 ...]"
		(default: get the list from local resolver)
        --ns-servers-v6=STRING
                list of IPv6 name servers separated by space (name and IP separated by comma): "NAME,IP[ NAME,IP2 ...]"
		(default: get the list from local resolver)
        --rdds43-servers=STRING
                list of RDDS43 servers separated by comma: "NAME1,NAME2,..."
        --rdds80-servers=STRING
                list of RDDS80 servers separated by comma: "NAME1,NAME2,..."
        --epp-servers=STRING
                list of EPP servers separated by comma: "NAME1,NAME2,..."
        --epp-user
                specify EPP username
	--epp-cert
                path to EPP Client certificates file
	--epp-servercert
                path to EPP Server certificates file
	--epp-privkey
                path to EPP Client private key file (unencrypted)
	--epp-serverid
                specify expected EPP Server ID string in reply
	--epp-test-prefix=STRING
                this string represents DOMAIN (in DOMAIN.TLD) to use in EPP commands
	--epp-commands
                path to a directory on the Probe Node containing EPP command templates
        --rdds-ns-string=STRING
                name server prefix in the WHOIS output
		(default: $cfg_default_rdds_ns_string)
        --rdds-test-prefix=STRING
		domain test prefix for RDDS monitoring (needed only if rdds servers specified)
        --only-cron
		only create cron jobs and exit
        --help
                display this message
EOF
exit(1);
}

sub validate_input {
    my $msg = "";

    return if (defined($OPTS{'only-cron'}));

    $msg  = "TLD must be specified (--tld)\n" unless (defined($OPTS{'tld'}));
    $msg .= "at least one IPv4 or IPv6 must be enabled (--ipv4 or --ipv6)\n" unless ($OPTS{'ipv4'} or $OPTS{'ipv6'});
    $msg .= "DNS test prefix must be specified (--dns-test-prefix)\n" unless (defined($OPTS{'dns-test-prefix'}));
    $msg .= "RDDS test prefix must be specified (--rdds-test-prefix)\n" if ((defined($OPTS{'rdds43-servers'}) and !defined($OPTS{'rdds-test-prefix'})) or
									    (defined($OPTS{'rdds80-servers'}) and !defined($OPTS{'rdds-test-prefix'})));
    $msg .= "none or both --rdds43-servers and --rdds80-servers must be specified\n" if ((defined($OPTS{'rdds43-servers'}) and !defined($OPTS{'rdds80-servers'})) or
											 (defined($OPTS{'rdds80-servers'}) and !defined($OPTS{'rdds43-servers'})));
    if ($OPTS{'epp-servers'}) {
	$msg .= "EPP user must be specified (--epp-user)\n" unless ($OPTS{'epp-user'});
	$msg .= "EPP Client certificate file must be specified (--epp-cert)\n" unless ($OPTS{'epp-cert'});
	$msg .= "EPP Client private key file must be specified (--epp-privkey)\n" unless ($OPTS{'epp-privkey'});
	$msg .= "EPP server ID must be specified (--epp-serverid)\n" unless ($OPTS{'epp-serverid'});
	$msg .= "EPP domain test prefix must be specified (--epp-test-prefix)\n" unless ($OPTS{'epp-serverid'});
	$msg .= "EPP Server certificate file must be specified (--epp-servercert)\n" unless ($OPTS{'epp-servercert'});
    }

    unless ($msg eq "") {
	print($msg);
	usage();
    }
}

sub lc_options {
    foreach my $key (keys(%OPTS))
    {
	foreach ("tld", "rdds43-servers", "rdds80-servers=s", "epp-servers", "ns-servers-v4", "ns-servers-v6")
	{
	    $OPTS{$_} = lc($OPTS{$_}) if ($key eq $_);
	}
    }
}

sub create_probe_status_host {
    my $groupid = shift;

    my $name = 'Probes Status';

    my $hostid = create_host({'groups' => [{'groupid' => $groupid}],
                                          'host' => $name,
                                          'interfaces' => [{'type' => 1, 'main' => 1, 'useip' => 1,
                                                            'ip'=> '127.0.0.1',
                                                            'dns' => '', 'port' => '10050'}]
                });

    my $interfaceid = $zabbix->get('hostinterface', {'hostids' => $hostid, 'output' => ['interfaceid']});

    my $options = {'name' => 'Total number of probes for DNS tests',
                                              'key_'=> 'online.nodes.pl[total,dns]',
                                              'hostid' => $hostid,
					      'interfaceid' => $interfaceid->{'interfaceid'},
                                              'applications' => [get_application_id('Probes availability', $hostid)],
                                              'type' => 10, 'value_type' => 3,
					      'delay' => 300,
                                              };
    create_item($options);

    $options = {'name' => 'Number of online probes for DNS tests',
                                              'key_'=> 'online.nodes.pl[online,dns]',
                                              'hostid' => $hostid,
					      'interfaceid' => $interfaceid->{'interfaceid'},
                                              'applications' => [get_application_id('Probes availability', $hostid)],
                                              'type' => 10, 'value_type' => 3,
					      'delay' => 60,
                                              };
    create_item($options);

    $options = {'name' => 'Total number of probes for EPP tests',
                                              'key_'=> 'online.nodes.pl[total,epp]',
                                              'hostid' => $hostid,
                                              'interfaceid' => $interfaceid->{'interfaceid'},
                                              'applications' => [get_application_id('Probes availability', $hostid)],
                                              'type' => 10, 'value_type' => 3,
                                              'delay' => 300,
                                              };
    create_item($options);

    $options = {'name' => 'Number of online probes for EPP tests',
                                              'key_'=> 'online.nodes.pl[online,epp]',
                                              'hostid' => $hostid,
                                              'interfaceid' => $interfaceid->{'interfaceid'},
                                              'applications' => [get_application_id('Probes availability', $hostid)],
                                              'type' => 10, 'value_type' => 3,
                                              'delay' => 60,
                                              };
    create_item($options);

    $options = {'name' => 'Total number of probes for RDDS tests',
                                              'key_'=> 'online.nodes.pl[total,rdds]',
                                              'hostid' => $hostid,
                                              'interfaceid' => $interfaceid->{'interfaceid'},
                                              'applications' => [get_application_id('Probes availability', $hostid)],
                                              'type' => 10, 'value_type' => 3,
                                              'delay' => 300,
                                              };
    create_item($options);

    $options = {'name' => 'Number of online probes for RDDS tests',
                                              'key_'=> 'online.nodes.pl[online,rdds]',
                                              'hostid' => $hostid,
                                              'interfaceid' => $interfaceid->{'interfaceid'},
                                              'applications' => [get_application_id('Probes availability', $hostid)],
                                              'type' => 10, 'value_type' => 3,
                                              'delay' => 60,
                                              };
    create_item($options);

    $options = {'name' => 'Total number of probes with enabled IPv4',
                                              'key_'=> 'online.nodes.pl[total,ipv4]',
                                              'hostid' => $hostid,
                                              'interfaceid' => $interfaceid->{'interfaceid'},
                                              'applications' => [get_application_id('Probes availability', $hostid)],
                                              'type' => 10, 'value_type' => 3,
                                              'delay' => 300,
                                              };
    create_item($options);

    $options = {'name' => 'Number of online probes with enabled IPv4',
                                              'key_'=> 'online.nodes.pl[online,ipv4]',
                                              'hostid' => $hostid,
                                              'interfaceid' => $interfaceid->{'interfaceid'},
                                              'applications' => [get_application_id('Probes availability', $hostid)],
                                              'type' => 10, 'value_type' => 3,
                                              'delay' => 60,
                                              };
    create_item($options);

    $options = {'name' => 'Total number of probes with enabled IPv6',
                                              'key_'=> 'online.nodes.pl[total,ipv6]',
                                              'hostid' => $hostid,
                                              'interfaceid' => $interfaceid->{'interfaceid'},
                                              'applications' => [get_application_id('Probes availability', $hostid)],
                                              'type' => 10, 'value_type' => 3,
                                              'delay' => 300,
                                              };
    create_item($options);

    $options = {'name' => 'Number of online probes with enabled IPv6',
                                              'key_'=> 'online.nodes.pl[online,ipv6]',
                                              'hostid' => $hostid,
                                              'interfaceid' => $interfaceid->{'interfaceid'},
                                              'applications' => [get_application_id('Probes availability', $hostid)],
                                              'type' => 10, 'value_type' => 3,
                                              'delay' => 60,
                                              };
    create_item($options);

    $options = { 'description' => 'DNS-PROBE: 12.2 - Online probes for test [{ITEM.LASTVALUE1}] is less than [{$RSM.DNS.PROBE.ONLINE}]',
                         'expression' => '{'.$name.':online.nodes.pl[online,dns].last(0)}<{$RSM.DNS.PROBE.ONLINE}',
                        'priority' => '5',
                };

    create_trigger($options);

    $options = { 'description' => 'RDDS-PROBE: 12.2 - Online probes for test [{ITEM.LASTVALUE1}] is less than [{$RSM.RDDS.PROBE.ONLINE}]',
                         'expression' => '{'.$name.':online.nodes.pl[online,rdds].last(0)}<{$RSM.RDDS.PROBE.ONLINE}',
                        'priority' => '5',
                };

    create_trigger($options);

    $options = { 'description' => 'EPP-PROBE: 12.2 - Online probes for test [{ITEM.LASTVALUE1}] is less than [{$RSM.EPP.PROBE.ONLINE}]',
                         'expression' => '{'.$name.':online.nodes.pl[online,epp].last(0)}<{$RSM.EPP.PROBE.ONLINE}',
                        'priority' => '5',
                };

    create_trigger($options);

    $options = { 'description' => 'IPv4-PROBE: 12.2 - Online probes with IPv4 [{ITEM.LASTVALUE1}] is less than [{$RSM.IP4.MIN.PROBE.ONLINE}]',
                         'expression' => '{'.$name.':online.nodes.pl[online,ipv4].last(0)}<{$RSM.IP4.MIN.PROBE.ONLINE}',
                        'priority' => '5',
                };

    create_trigger($options);

    $options = { 'description' => 'IPv6-PROBE: 12.2 - Online probes with IPv6 [{ITEM.LASTVALUE1}] is less than [{$RSM.IP6.MIN.PROBE.ONLINE}]',
                         'expression' => '{'.$name.':online.nodes.pl[online,ipv6].last(0)}<{$RSM.IP6.MIN.PROBE.ONLINE}',
                        'priority' => '5',
                };

    create_trigger($options);
}

sub add_default_actions() {

}
