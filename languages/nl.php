<?php

return array(
    'dutch'                 => 'Nederlands',

    //General
    'back'                  => 'terug',
    'attribute'             => 'Attribuut',
    'value'                 => 'Waarde',
    'post_data'             => 'POST Data',
    'processing'            => 'Verbinden met de dienst',
    'processing_waiting'    => 'Wachten op een reactie van de dienst.',
    'processing_long'       => 'Wees a.u.b. geduldig, het kan even duren...',
    'go_back'               => '&lt;&lt; Ga terug',
    'note'                  => 'Mededeling',
    'note_no_script'        => 'Uw browser ondersteunt geen JavaScript. Druk op onderstaande knop om door te gaan.',
    'authentication_urls'   => 'Authenticatie URLs',
    'timestamp'             => 'Timestamp',

     // Feedback
    'requestId'             => 'Uniek Request ID',
    'identityProvider'      => 'Identity Provider',
    'serviceProvider'       => 'Service Provider',
    'userAgent'             => 'User Agent',
    'ipAddress'             => 'IP-adres',
    'statusCode'            => 'Statuscode',
    'statusMessage'         => 'Statusbericht',

    //WAYF
    'idp_selection_title'       => 'Organisatie selectie - %s',
    'idp_selection_subheader'   => 'Login via uw eigen organisatie',
    'search'                    => 'Zoek een organisatie...',
    'idp_selection_desc'        => 'Selecteer een organisatie om aan te melden bij <i>%s</i>',
    'our_suggestion'            => 'Eerder gekozen:',
    'idps_with_access'          => 'Organisaties met toegang',
    'no_access'                 => 'Geen toegang.',
    'no_access_more_info'       => 'Geen toegang. &raquo;',
    'no_results'                => 'Geen resultaten gevonden.',
    'log_in_to'                 => 'Selecteer een organisatie om aan te melden bij',
    'press_enter_to_select'     => 'Druk op enter om te kiezen',
    'loading_idps'              => 'Organisaties worden geladen...',
    'edit'                      => 'Bewerken',
    'done'                      => 'Klaar',
    'remove'                    => 'Verwijderen',
    'request_access'            => 'Toegang aanvragen',
    'no_idp_results'            => 'Uw zoekterm heeft geen resultaten opgeleverd.',
    'no_idp_results_request_access' => 'Kunt u uw organisatie niet vinden? &nbsp;<a href="#no-access" class="noaccess">Vraag toegang aan</a>&nbsp;of pas uw zoekopdracht aan.',
    'more_idp_results'          => '%d resultaten worden niet getoond. Verfijn uw zoekopdracht voor specifiekere resultaten.',
    'return_to_sp'              => 'Keer terug naar Service Provider',

    //Footer
    'service_by'            => 'Deze dienst is verbonden via',
    'serviceprovider_link'  => '<a href="https://www.ssc-ict.nl/" target="_blank">SSC-ICT</a>',
    'terms_of_service_link' => '<a href="https://wiki.surfnet.nl/display/conextsupport/Terms+of+Service+%28NL%29" target="_blank">Gebruiksvoorwaarden</a>',
    'footer'                => '<a href="https://www.ssc-ict.nl/" target="_blank">SSC-ICT</a>&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;<a href="https://wiki.surfnet.nl/display/conextsupport/Terms+of+Service+%28NL%29">Gebruiksvoorwaarden</a>',

    //Help
    'help'                  => 'Help',
    'help_header'           => 'Help',
    'help_description'      => '<p>Heeft u vragen over dit scherm of de SSC-ICT dienstverlening, bekijk dan de antwoorden bij de FAQ hieronder.</p>

    <p>Staat uw vraag er niet bij, of bent u niet tevreden met een antwoord? Stuur dan een mail naar <a href="mailto:FederatieveServices@minbzk.nl">FederatieveServices@minbzk.nl</a></p>',

    'close_question'        =>      'Sluit',

    //Help questions
		// general help questions
    'question_surfconext'               =>      'Wat is OpenConext?',
    'answer_surfconext'                 =>      '<p>OpenConext is een verbindingsinfrastructuur die een aantal bouwstenen voor online samenwerking met elkaar verbindt. Die bouwstenen zijn services voor federatieve authenticatie, groepsbeheer, sociale netwerken en cloud applicaties van verschillende aanbieders. Met OpenConext is het mogelijk om met uw eigen organisatieaccount toegang te krijgen tot diensten van verschillende aanbieders.</p>',
    'question_log_in'                   =>      'Hoe werkt inloggen via OpenConext?',
    'answer_log_in'                     =>      '<ul>
                            <li>U selecteert in dit scherm uw eigen organisatie.</li>
                            <li>U wordt doorgestuurd naar een inlogpagina van uw eigen organisatie. Daar logt u in.</li>
                            <li>Uw organisatie geeft door aan OpenConext dat u succesvol bent ingelogd.</li>
                            <li>U wordt doorgestuurd naar de dienst waarop u hebt ingelogd om deze te gaan gebruiken.</li>
                        </ul>',
    'question_security'                 =>      'Is de OpenConext infrastructuur veilig?',
    'answer_security'                   =>      '<p>Uw organisatie en SSC-ICT hechten veel belang aan de privacy van gebruikers.<br />
<br />
Persoonsgegevens worden alleen verstrekt aan een dienstaanbieder wanneer dat noodzakelijk is voor het gebruik van de dienst. Contractuele afspraken tussen uw organisatie, SSC-ICT en de dienstaanbieder waarborgen dat er zorgvuldig wordt omgegaan met uw persoonsgegevens.<br />
<br />
Heeft u vragen ten aanzien van het privacybeleid van SSC-ICT, mail deze dan naar de beheerders via <a href="mailto:FederatieveServices@minbzk.nl">FederatieveServices.minbzk.nl</a>.
</p>',

    	// consent help questions
    'question_consentscreen'           	=>      'Waarom dit scherm?',
    'answer_consentscreen'             	=>      '<p>Om toegang te krijgen tot deze dienst is het noodzakelijk dat een aantal persoonlijke gegevens wordt gedeeld met deze dienst.</p>',
    'question_consentinfo'           	=>      'Wat gebeurt er met mijn gegevens?',
    'answer_consentinfo'             	=>      '<p>Indien u akkoord gaat met het verstrekken van uw gegevens aan de dienst dan zullen de getoonde gegevens met deze dienst gedeeld worden. De dienstverlener zal de gegevens gebruiken en mogelijk opslaan voor een goede werking van de dienst.</p>',
    'question_consentno'           		=>      'Wat gebeurt er als ik mijn gegevens niet wil delen?',
    'answer_consentno'             		=>      '<p>Als u niet akkoord gaat met het delen van uw gegevens kun u geen gebruik maken van de dienst. De getoonde gegevens zullen in dit geval niet met de dienst worden gedeeld.</p>',
    'question_consentagain'           	=>      'Ik heb eerder al toestemming gegeven voor het delen van mijn gegevens, waarom krijg ik deze vraag opnieuw?',
    'answer_consentagain'             	=>      '<p>Indien de gegevens die doorgegeven worden aan deze dienst zijn gewijzigd zal er nogmaals gevraagd worden of u akkoord gaat met het delen van uw gegevens.</p>',

		// WAYF help questions
    'question_screen'                   =>      'Waarom dit scherm?',
    'answer_screen'                     =>      '<p>U kunt met uw organisatieaccount inloggen bij deze dienst. In dit scherm geeft u aan via welke organisatie u wilt inloggen.</p>',
    'question_institution_not_listed'   =>      'Ik zie mijn organisatie er niet tussen staan, wat nu?',
    'answer_institution_not_listed'     =>      '<p>Staat uw organisatie niet in de lijst? Dan is uw organisatie waarschijnlijk nog niet aangesloten op OpenConext. Ga terug naar de pagina van de dienst; soms biedt een dienst ook alternatieve manieren om in te loggen.</p>',
    'question_institution_no_access'    =>      'Mijn organisatie geeft geen toegang tot deze dienst, wat nu?',
    'answer_institution_no_access'      =>      '<p>Het kan zijn dat uw organisatie wel is aangesloten op OpenConext maar (nog) geen afspraken heeft gemaakt met de dienstaanbieder over het gebruik van deze dienst. Wij zullen uw verzoek doorsturen naar de verantwoordelijke binnen uw organisatie die de toegang tot diensten organiseert. Wellicht is uw verzoek voor uw organisatie aanleiding om alsnog afspraken met deze dienstaanbieder te maken.</p>',
    'question_asked_institution_access'  =>      'Ik heb toegang aangevraagd voor mijn organisatie, maar mijn organisatie geeft nog steeds geen toegang. Waarom niet?',
    'answer_asked_institution_access'    =>      '<p>Blijkbaar is uw organisatie nog niet tot een overeenkomst met de dienstaanbieder gekomen of, het gebruik van deze dienst is niet wenselijk binnen uw organisatie. SSC-ICT heeft geen controle over de snelheid waarmee u antwoord of toegang krijgt. Die verantwoordelijkheid en zeggenschap ligt bij de organisatie.</p>',
    'question_cannot_select'            =>      'Ik kan in mijn browser mijn instelling niet selecteren, wat nu?',
    'answer_cannot_select'              =>      '<p>Het keuzescherm van OpenConext is te gebruiken in de meest gangbare browsers waaronder, Internet Explorer, Firefox, Chrome en Safari. Andere browsers worden mogelijk niet ondersteund. Verder moet uw browser het gebruik van cookies en javascript toestaan.</p>',

    // Request Access Form
    'sorry'                 => 'Helaas,',
    'form_description'      => 'heeft geen toegang tot deze dienst. Wat nu?</h2>
            <p>Wilt u toch graag toegang tot deze dienst, vul dan
      het onderstaande formulier in. Wij sturen uw verzoek door naar de juiste persoon binnen uw organisatie.</p>',
    'request_access_instructions' => '<h2>Helaas, u heeft geen toegang tot de dienst die u zoekt. Wat nu?</h2>
                                <p>Wilt u toch graag toegang tot deze dienst, vul dan het onderstaande formulier in.
                                   Wij sturen uw verzoek door naar de juiste persoon binnen uw organisatie.</p>',
    'name'                  => 'Naam',
    'name_error'            => 'Vul uw naam in',
    'email'                 => 'E-mail',
    'email_error'           => 'Vul uw (correcte) e-mailadres in',
    'institution'           => 'Instelling',
    'institution_error'     => 'Vul uw organisatie in',
    'comment'               => 'Toelichting',
    'comment_error'         => 'Vul een toelichting in',
    'cancel'                => 'Annuleren',
    'send'                  => 'Verstuur',
    'close'                 => 'Sluiten',

    'send_confirm'          => 'Uw verzoek is verzonden',
    'send_confirm_desc'     => '<p>SSC-ICT stuurt uw verzoek door aan de juiste persoon binnen uw organisatie. Het is aan deze persoon om actie te ondernemen op basis van uw verzoek. Het kan zijn dat er nog afspraken gemaakt moeten worden tussen uw organisatie en de dienstaanbieder.</p>

    <p>SSC-ICT faciliteert het doorsturen van uw verzoek maar heeft geen controle over de snelheid waarmee u antwoord of toegang krijgt.</p>

    <p>Heeft u vragen over uw verzoek, neem dan contact op met <a href="mailto:FederatieveServices@minbzk.nl">FederatieveServices@minbzk.nl</a></p>',

    // Delete User
    'deleteuser_success_header'         => 'OpenConext exit procedure',
    'deleteuser_success_subheader'      => 'U bent bijna klaar...',
    'deleteuser_success_desc'           => '<strong>Belangrijk!</strong> Om de exit procedure succesvol af te ronden, moet u nu de browser afsluiten.',


    // Consent
    'external_link'                     => 'opent in een nieuw venster',
    'consent_header'                    => '%s verzoekt uw informatie',
    'consent_subheader'                 => '%s verzoekt uw informatie',
    'consent_intro'                     => '%s verzoekt deze informatie die %s voor jou heeft opgeslagen:',
    'consent_idp_provides'              => 'wilt de volgende informatie vrijgeven:',
    'consent_sp_is_provided'            => 'aan',
    'consent_terms_of_service'          => 'Deze informatie zal worden doorgegeven aan %s. Gebruiksvoorwaarden van %s en %s zijn van toepassing.',

    'consent_accept'                    => 'Ja, deel deze gegevens',
    'consent_decline'                   => 'Nee, ik wil geen gebruik maken van deze dienst',
    'consent_notice'                    => '(We zullen dit nogmaals vragen als uw informatie wijzigt)',

    // New Consent
    'consent_header_info'               => 'Verzoek voor doorgeven van uw informatie',
    'consent_sp_idp_info'               => 'Om met uw organisatieaccount in te kunnen loggen op de dienst <strong class="service-provider">%1$s</strong> maakt <strong class="identity-provider">%2$s</strong> gebruik van OpenConext. Voor het functioneren van deze dienst is het noodzakelijk dat <strong class="identity-provider">%2$s</strong> een aantal gegevens via OpenConext deelt met deze dienst. Hiervoor is uw toestemming nodig. Het gaat om de volgende gegevens:',
    'consent_aggregated_attributes_info' => '<strong class="service-provider">%1$s</strong> heeft ook toegang nodig tot gegevens uit de gegevensbron <strong class="attribute-source">%2$s</strong>. Het gaat om de volgende aanvullende gegevens:',
    'consent_attribute_source_voot'     => 'Groepslidmaatschap',
    'consent_attribute_source_sab'      => 'SURFnet Autorisatie Beheer',
    'consent_attribute_source_orcid'    => 'ORCID iD',

    'sp_terms_of_service'               => 'Bekijk de %s\'s <a href="%s" target="_blank">gebruiksvoorwaarden</a>',
    'name_id'                           => 'OpenConext gebruikers ID',

    // Error screens
    'error_404'                         => '404 - Pagina niet gevonden',
    'error_404_desc'                    => 'De pagina is niet gevonden.',
    'error_help_desc'                   => '<p>
        Bezoek <a href="https://support.surfconext.nl/" target="_blank">de OpenConext support pagina\'s</a> voor ondersteuning bij deze foutmelding. Hier kunt u ook vinden hoe u contact kunt opnemen met het supportteam als de fout aanblijft.
    </p>',
    'error_no_consent'                  => 'Niet mogelijk om verder te gaan naar dienst',
    'error_no_consent_desc'             => 'Deze applicatie kan enkel worden gebruikt wanneer de vermelde informatie wordt gedeeld.<br /><br />

Als u deze applicatie wilt gebruiken moet u:<br />
<ul><li>de browser herstarten</li>
<li>opnieuw inloggen</li>
<li>uw informatie delen</li></ul>',
    'error_no_idps'                     => 'Error - Geen organisaties gevonden',
    'error_no_idps_desc'                => '<p>
        De dienst die u probeert te benaderen (de &lsquo;Service Provider&rsquo;) is niet toegankelijk via de OpenConext-infrastructuur.<br /><br />
        Bezoek <a href="https://wiki.surfnet.nl/pages/viewpage.action?pageId=54691917" target="_blank">de OpenConext support pagina\'s</a> voor meer ondersteuning bij deze foutmelding, als u denkt daadwerkelijk toegang te moeten hebben tot deze dienst.
        <br /><br />
    </p>',
    'error_session_lost'                => 'Error - Sessie is verloren gegaan',
    'error_session_lost_desc'           => '<p>
We weten helaas niet waar u heen wilt. Heeft u te lang gewacht? Probeer het dan eerst opnieuw. Accepteert uw browser wel cookies? Maakt u gebruik van een te oude link of bookmark?<br /><br />
Bezoek <a href="https://wiki.surfnet.nl/pages/viewpage.action?pageId=52331093" target="_blank">de OpenConext support pagina\'s</a> voor meer ondersteuning bij deze foutmelding. Hier kun u ook vinden hoe u contact kunt opnemen met het supportteam als de fout aanblijft.
        <br /><br />
    </p>',
    'error_dissimilar_workflow_state'       => 'Error - Verschillende productie statussen',
    'error_dissimilar_workflow_state_desc'  => '<p>
De dienst die u probeert te benaderen (de &lsquo;Service Provider&rsquo;) is nog niet beschikbaar via de OpenConext infrastructuur.
Ga alstublieft <a href="javascript:history.back();">terug</a> en neem contact op met de beheerders van SSC-ICT via <a href="mailto:FederatieveServices@minbzk.nl">FederatieveServices@minbzk.nl</a>.
<br /><br />
</p>',
    'error_authorization_policy_violation'            => 'Error - Geen toegang',
    'error_authorization_policy_violation_desc'       => '<p>
        U bent succesvol ingelogd bij uw organisatie, maar u kunt geen gebruik maken van deze dienst omdat u geen toegang heeft. Voor deze dienst (de &lsquo;Service Provider&rsquo;) heeft uw organisatie met <i>autorisatieregels</i> ingesteld dat alleen bepaalde gebruikers toegang krijgen. Neem contact op met de (IT-)servicedesk van uw organisatie als u vindt dat u wel toegang moet hebben.
    </p>',
    'error_authorization_policy_violation_name'       => 'Omschrijving autorisatieregels',
    'error_authorization_policy_violation_info'       => 'Bericht van uw organisatie: ',
    'error_no_message'                  => 'Error - Geen bericht ontvangen',
    'error_no_message_desc'             => 'We verwachtten een bericht, maar we hebben er geen ontvangen. Er is iets fout gegaan. Probeer het alstublieft opnieuw.',
    'error_invalid_acs_location'        => 'De opgegeven "Assertion Consumer Service" is onjuist of bestaat niet.',
    'error_invalid_acs_binding'        => 'Onjuist ACS Binding Type',
    'error_invalid_acs_binding_desc'        => 'Het opgegeven of geconfigureerde "Assertion Consumer Service" Binding Type is onjuist of bestaat niet.',
    'error_unknown_preselected_idp' => 'Error - Organisatie is niet gekoppeld aan dienst',
    'error_unknown_preselected_idp_desc' => '<p>
        De organisatie waarbij u wilt inloggen heeft toegang tot deze dienst niet geactiveerd. Dat betekent dat jij geen gebruik kunt maken van deze dienst via OpenConext. Neem contact op met de helpdesk van uw organisatie als u toegang wilt krijgen tot deze dienst. Geef daarbij aan om welke dienst het gaat (de &lsquo;Service Provider&rsquo;) en waarom u toegang wilt.
    </p>',
    'error_unknown_service_provider'              => 'Error - Kan geen metadata ophalen voor EntityID \'%s\'',
    'error_unknown_service_provider_desc'     => '<p>
        Er kon geen Service Provider worden gevonden met het opgegeven EntityID.
        Neem contact op met de beheerders op <a href="mailto:FederatieveServices@minbzk.nl">FederatieveServices@minbzk.nl</a>.
    </p>',

    'error_unknown_issuer'              => 'Error - Onbekende dienst',
    'error_unknown_issuer_desc'     => '<p>
De door u aangevraagde dienst kon niet worden gevonden. Bezoek <a href="https://wiki.surfnet.nl/pages/viewpage.action?pageId=53018683" target="_blank">de OpenConext support pagina\'s</a> voor meer ondersteuning bij deze foutmelding.
    </p>',
    'error_generic'                     => 'Error - Foutmelding',
    'error_generic_desc'                => '<p>
Inloggen is niet gelukt en we kunnen u niet precies vertellen waarom. Probeer het eerst eens opnieuw, en bezoek anders <a href="https://wiki.surfnet.nl/pages/viewpage.action?pageId=52331091" target="_blank">de OpenConext support pagina\'s</a> voor ondersteuning bij deze foutmelding.
    </p>',
    'error_missing_required_fields'     => 'Error - Verplichte velden ontbreken',
    'error_missing_required_fields_desc'=> '<p>
        Uw organisatie geeft niet de benodigde informatie vrij. Daarom kunt u deze applicatie niet gebruiken.
    </p>
    <p>
        Neem alstublieft contact op met uw organisatie. Geef hierbij de onderstaande informatie door.
    </p>
    <p>
        Omdat uw organisatie niet de juiste attributen aan OpenConext doorgeeft is het inloggen mislukt. De volgende attributen zijn vereist om succesvol in te loggen via het OpenConext platform:
        <ul>
            <li>UID</li>
            <li>schacHomeOrganization</li>
        </ul>
    </p>',
    'error_group_oauth'            =>  'Error - Groepautorisatie is mislukt',
    'error_group_oauth_desc'       => '<p>
        De extere groepprovider <b>%s</b> retourneerde een fout.<br />
        Neem contact op met de beheerders via <a href="mailto:FederatieveServices@minbzk.nl">FederatieveServices@minbzk.nl</a>.
        <br />
    </p>',

    'error_received_error_status_code'     => 'Error - Fout bij Identity Provider',
    'error_received_error_status_code_desc'=> '<p>
Uw organisatie heeft u de toegang geweigerd tot deze dienst. U zult dus contact moeten opnemen met de (IT-)servicedesk van uw eigen organisatie om te kijken of dit verholpen kan worden.
    </p>',
    'error_received_invalid_response'        => 'Error - Ongeldig antwoord van Identity Provider',
    'error_received_invalid_signed_response' => 'Error - Ongeldige handtekening op antwoord Identity Provider',
    'error_stuck_in_authentication_loop' => 'Error - U zat vast in een oneindige lus',
    'error_stuck_in_authentication_loop_desc' => '<p>
        U bent succesvol ingelogd bij uw organisatie maar de dienst waar u naartoe wilt stuurt u weer terug naar OpenConext. Omdat u succesvol bent ingelogd, stuurt OpenConext u weer naar de dienst, wat resulteert in een oneindig lus. Dit komt waarschijnlijk door een foutje aan de kant van de dienst. Bezoek <a href="https://support.surfconext.nl" target="_blank">de OpenConext support pagina\'s</a> voor meer ondersteuning bij deze foutmelding.
    </p>',

    /**
     * %1 AttributeName
     * %2 Options
     * %3 (optional) Value
     * @url http://nl3.php.net/sprintf
     */
    'error_attribute_validator_type_uri'            => '\'%3$s\' is geen geldige URI',
    'error_attribute_validator_type_urn'            => '\'%3$s\' is geen geldige URN',
    'error_attribute_validator_type_url'            => '\'%3$s\' is geen geldige URL',
    'error_attribute_validator_type_hostname'       => '\'%3$s\' is geen geldige hostname',
    'error_attribute_validator_type_emailaddress'   => '\'%3$s\' is geen geldig emailadres',
    'error_attribute_validator_minlength'           => '\'%3$s\' is niet lang genoeg (minimaal %2$d karakters)',
    'error_attribute_validator_maxlength'           => '\'%3$s\' is te lang (maximaal %2$d karakters)',
    'error_attribute_validator_min'                 => '%1$s heeft minimaal %2$d waardes nodig (%3$d gegeven)',
    'error_attribute_validator_max'                 => '%1$s heeft maximaal %2$d waardes (%3$d gegeven)',
    'error_attribute_validator_regex'               => '\'%3$s\' voldoet niet aan de voorwaarden voor waardes van dit attribuut (%2$s)',
    'error_attribute_validator_not_in_definitions'  => '%1$s is niet bekend in het OpenConext schema',
    'error_attribute_validator_allowed'             => '\'%3$s\' is geen toegestane waarde voor dit attribuut',
    'error_attribute_validator_availability'        => '\'%3$s\' is a gereserveerde SchacHomeOrganization voor een andere Identity Provider',

    'attributes_validation_succeeded' => 'Authenticatie geslaagd',
    'attributes_validation_failed'    => 'Sommige attributen kunnen niet gevalideerd worden',
    'attributes_data_mailed'          => 'De attribuutdata zijn gemaild',
    'idp_debugging_title'             => 'Toon verkregen response van Identity Provider',
    'retry'                           => 'Opnieuw',

    'attributes' => 'Attributen',
    'validation' => 'Validatie',
    'idp_debugging_mail_explain' => 'Indien gevraagd door de beheerders,
                                        gebruik de "Mail naar SSC-ICT" knop hieronder
                                        om de informatie op dit scherm naar de beheerders te e-mailen.',
    'idp_debugging_mail_button' => 'Mail naar SSC-ICT',

    // Logout
    'logout' => 'uitloggen',
    'logout_description' => 'Deze applicatie maakt gebruik van centrale login. Hiermee is het mogelijk om met single sign on bij verschillende applicaties in te loggen. Om er 100% zeker van te zijn dat u uitgelogd bent, moet u de browser helemaal afsluiten.',
    'logout_information_link' => '<a href="https://wiki.surfnet.nl/display/conextsupport/Uitloggen+SURFconext">Meer informatie over veilig uitloggen</a>',

    // Internal
    'info_mail_link' => '<a href="FederatieveServices@minbzk.nl">FederatieveServices@minbzk.nl</a>',
);
