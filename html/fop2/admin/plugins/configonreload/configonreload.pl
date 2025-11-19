$AMI_Event_Handler{'configonreload'}{'RELOAD'} = sub {
    my @allreturn;
    my $script = defined($main::pluginconfig{'configonreload'}{'scriptReload'}{''})?$main::pluginconfig{'configonreload'}{'scriptReload'}{''}:'/usr/bin/php -f /var/www/html/fop2/admin/update_conf.php';

    my $final_script = "$script &";

    if(!defined($main::pluginvariable{'configonreload'}{'enabled'}{'enabled'})) {
        $main::pluginvariable{'configonreload'}{'enabled'}{'enabled'}=1;
        system($final_script);
        $timer_readyreload = AnyEvent->timer(
            after    => 1,
            cb       => sub {
                delete $main::pluginvariable{'configonreload'}{'enabled'}{'enabled'};
                $return = "Action: UserEvent\r\n";
                $return .= "UserEvent: READYRELOAD\r\n";
                $return .= "Channel: ZAP/1\r\n\r\n";
                main::send_command_to_managers($return);
            }
        );
        # If we modify queues.conf and reload asterisk, we want to retrieve initial queue status again
        $return = "Action: QueueStatus\r\n";
        $return .= "ActionID: initialqueuestatus\r\n";
        push @allreturn, $return;

    } else {
         print "skip reload porque es muy pronto, hay reload anterior\n";
    }
    return @allreturn;
};

$AMI_Event_Handler{'configonreload'}{'USEREVENT'} = sub {
    my $event = shift;
    my @allreturn;
    my @keys =  keys %$event;

    my $userevent   = ${$event}{UserEvent};
    if ($userevent eq "Reload") {
        my $script = defined($main::pluginconfig{'configonreload'}{'scriptReload'}{''})?$main::pluginconfig{'configonreload'}{'scriptReload'}{''}:'/usr/bin/php -f /var/www/html/fop2/admin/update_conf.php';
        my $final_script = "$script &";
        if(!defined($main::pluginvariable{'configonreload'}{'enabled'}{'enabled'})) {
            $main::pluginvariable{'configonreload'}{'enabled'}{'enabled'}=1;
            system($final_script);
            $timer_readyreload = AnyEvent->timer(
                after    => 1,
                cb       => sub {
                    delete $main::pluginvariable{'configonreload'}{'enabled'}{'enabled'};
                    $return = "Action: UserEvent\r\n";
                    $return .= "UserEvent: READYRELOAD\r\n";
                    $return .= "Channel: ZAP/1\r\n\r\n";
                    main::send_command_to_managers($return);
                }
            );
        } else {
             print "skip reload porque es muy pronto, hay reload anterior\n";
        }
    }
    return @allreturn;
};
