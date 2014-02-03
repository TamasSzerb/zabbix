package Zabbix::Sender;
{
  $Zabbix::Sender::VERSION = '0.03';
}
# ABSTRACT: A pure-perl implementation of zabbix-sender.

use Moose;
use namespace::autoclean;

use JSON;
use IO::Socket;
use IO::Select;
use Net::Domain;


has 'server' => (
    'is'       => 'rw',
    'isa'      => 'Str',
    'required' => 1,
);

has 'port' => (
    'is'      => 'rw',
    'isa'     => 'Int',
    'default' => 10051,
);

has 'timeout' => (
    'is'      => 'rw',
    'isa'     => 'Int',
    'default' => 30,
);

has 'hostname' => (
    'is'      => 'rw',
    'isa'     => 'Str',
    'lazy'    => 1,
    'builder' => '_init_hostname',
);

has 'interval' => (
    'is'      => 'rw',
    'isa'     => 'Int',
    'default' => 1,
);

has 'retries' => (
    'is'      => 'rw',
    'isa'     => 'Int',
    'default' => 3,
);

has 'keepalive' => (
    'is'    => 'rw',
    'isa'   => 'Bool',
    'default' => 0,
);

has '_json' => (
    'is'      => 'rw',
    'isa'     => 'JSON',
    'lazy'    => 1,
    'builder' => '_init_json',
);

has '_last_sent' => (
    'is'      => 'rw',
    'isa'     => 'Int',
    'default' => 0,
);

has '_socket' => (
    'is'    => 'rw',
    'isa'   => 'Maybe[IO::Socket]',
);


sub _init_json {
    my $self = shift;

    my $JSON = JSON::->new->utf8();

    return $JSON;
}


sub _init_hostname {
    my $self = shift;

    return Net::Domain::hostname() . '.' . Net::Domain::hostdomain();
}


has 'zabbix_template_1_8' => (
    'is'      => 'ro',
    'isa'     => 'Str',
    'default' => "a4 b C4 C4 a*",
);


sub _encode_request {
    my $self  = shift;
    my $host  = shift;
    my $item  = shift;
    my $value = shift;
    my $clock = shift;

    my $data_ref = {
        'host'  => $host,
        'key'   => $item,
        'value' => $value,
    };
    $data_ref->{'clock'} = $clock if defined($clock);

    my $data = {
        'request' => 'sender data',
        'data'    => [$data_ref],
    };

    my $output = '';
    my $json   = $self->_json()->encode($data);

    # turn on byte semantics to get the real length of the string
    use bytes;
    my $length = length($json);
    no bytes;

    ## no critic (ProhibitBitwiseOperators)
    $output = pack(
        $self->zabbix_template_1_8(),
        "ZBXD", 0x01,
        ( $length & 0xFF ),
        ( $length & 0x00FF ) >> 8,
        ( $length & 0x0000FF ) >> 16,
        ( $length & 0x000000FF ) >> 24,
        0x00, 0x00, 0x00, 0x00, $json
    );
    ## use critic

    return $output;
}


sub _decode_answer {
    my $self = shift;
    my $data = shift;

    my ( $ident, $answer );
    $ident = substr( $data, 0, 4 ) if length($data) > 3;
    $answer = substr( $data, 13 ) if length($data) > 12;

    if ( $ident && $answer ) {
        if ( $ident eq 'ZBXD' ) {
            my $ref = $self->_json()->decode($answer);
            if ( $ref->{'response'} eq 'success' ) {
                return 1;
            }
        }
    }
    return;
}


# DGR: Anything but send just doesn't makes sense here. And since this is a pure-OO module
# and if the implementor avoids indirect object notation you should be fine.
## no critic (ProhibitBuiltinHomonyms)
sub send {
## use critic
    my $self  = shift;
    my $host  = shift;
    my $item  = shift;
    my $value = shift;
    my $clock = shift;

    my $status = 0;
    foreach my $i ( 1 .. $self->retries() ) {
        if ( $self->_send( $host, $item, $value, $clock ) ) {
            $status = 1;
            last;
        }
    }

    if ($status) {
        return 1;
    }
    else {
        return;
    }

}

sub _send {
    my $self  = shift;
    my $host  = shift;
    my $item  = shift;
    my $value = shift;
    my $clock = shift;

    if ( time() - $self->_last_sent() < $self->interval() ) {
        my $sleep = $self->interval() - ( time() - $self->_last_sent() );
        $sleep ||= 0;
        sleep $sleep;
    }

    $self->_connect() unless $self->_socket();
    $self->_socket()->send( $self->_encode_request( $host, $item, $value, $clock ) );
    my $Select  = IO::Select::->new($self->_socket());
    my @Handles = $Select->can_read( $self->timeout() );

    my $status = 0;
    if ( scalar(@Handles) > 0 ) {
        my $result;
        $self->_socket()->recv( $result, 1024 );
        if ( $self->_decode_answer($result) ) {
            $status = 1;
        }
    }
    $self->_disconnect() unless $self->keepalive();
    if ($status) {
        return $status;
    }
    else {
        return;
    }
}

sub _connect {
    my $self = shift;

    my $Socket = IO::Socket::INET::->new(
        PeerAddr => $self->server(),
        PeerPort => $self->port(),
        Proto    => 'tcp',
        Timeout  => $self->timeout(),
    ) or die("Could not create socket: $!");

    $self->_socket($Socket);

    return 1;
}

sub _disconnect {
    my $self = shift;

    if(!$self->_socket()) {
        return;
    }

    $self->_socket()->close();
    $self->_socket(undef);

    return 1;
}


sub DEMOLISH {
    my $self = shift;

    $self->_disconnect();

    return 1;
}

no Moose;
__PACKAGE__->meta->make_immutable;


1;    # End of Zabbix::Sender

__END__

=pod

=head1 NAME

Zabbix::Sender - A pure-perl implementation of zabbix-sender.

=head1 VERSION

version 0.03

=head1 SYNOPSIS

This code snippet shows how to send the value "OK" for the item "my.zabbix.item"
to the zabbix server/proxy at "my.zabbix.server.example" on port "10055".

    use Zabbix::Sender;

    my $Sender = Zabbix::Sender->new({
    	'server' => 'my.zabbix.server.example',
    	'port' => 10055,
    });
    $Sender->send('my.zabbix.item','OK');

=head1 NAME

Zabbix::Sender - A pure-perl implementation of zabbix-sender.

=head1 SUBROUTINES/METHODS

=head2 _init_json

Zabbix 1.8 uses a JSON encoded payload after a custom Zabbix header.
So this initializes the JSON object.

=head2 _init_hostname

The hostname of the sending instance may be given in the constructor.

If not it is detected here.

=head2 zabbix_template_1_8

ZABBIX 1.8 TEMPLATE

a4 - ZBXD
b  - 0x01
c4 - Length of Request in Bytes (64-bit integer), aligned left, padded with 0x00
c4 - dito
a* - JSON encoded request

This may be changed to a HashRef if future version of zabbix change the header template.

=head2 _encode_request

This method encodes the item and value as a json string and creates
the required header acording to the template defined above.

=head2 _decode_answer

This method tries to decode the answer received from the server.

=head2 send

Send the given item with the given value to the server.

Takes two arguments: item and value. Both should be scalars.

=head2 DEMOLISH

Disconnects any open sockets on destruction.

=head1 AUTHOR

"Dominik Schulz", C<< <"lkml at ds.gauner.org"> >>

=head1 BUGS

Please report any bugs or feature requests to C<bug-zabbix-sender at rt.cpan.org>, or through
the web interface at L<http://rt.cpan.org/NoAuth/ReportBug.html?Queue=Zabbix-Sender>.  I will be notified, and then you'll
automatically be notified of progress on your bug as I make changes.

=head1 SUPPORT

You can find documentation for this module with the perldoc command.

    perldoc Zabbix::Sender

You can also look for information at:

=over 4

=item * RT: CPAN's request tracker

L<http://rt.cpan.org/NoAuth/Bugs.html?Dist=Zabbix-Sender>

=item * AnnoCPAN: Annotated CPAN documentation

L<http://annocpan.org/dist/Zabbix-Sender>

=item * CPAN Ratings

L<http://cpanratings.perl.org/d/Zabbix-Sender>

=item * Search CPAN

L<http://search.cpan.org/dist/Zabbix-Sender/>

=back

=head1 ACKNOWLEDGEMENTS

This code is based on the documentation and sample code found at:

=over 4

=item http://www.zabbix.com/wiki/doc/tech/proto/zabbixsenderprotocol

=item http://www.zabbix.com/documentation/1.8/protocols

=back

=head1 LICENSE AND COPYRIGHT

Copyright 2011 Dominik Schulz.

This program is free software; you can redistribute it and/or modify it
under the terms of either: the GNU General Public License as published
by the Free Software Foundation; or the Artistic License.

See http://dev.perl.org/licenses/ for more information.

=head1 AUTHOR

Dominik Schulz <dominik.schulz@gauner.org>

=head1 COPYRIGHT AND LICENSE

This software is copyright (c) 2012 by Dominik Schulz.

This is free software; you can redistribute it and/or modify it under
the same terms as the Perl 5 programming language system itself.

=cut
