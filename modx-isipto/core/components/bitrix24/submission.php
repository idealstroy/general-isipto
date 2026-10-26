<?php
/** Create a contact-backed deal in the approved funnel; resume only confirmed phases. */
class IsiptoBitrix24Submission
{
    private $client;
    private $routing;

    public function __construct(IsiptoBitrix24Client $client, array $routing)
    {
        if (!isset($routing['categoryId'], $routing['stageId'], $routing['assignedById'])
            || !in_array((int) $routing['categoryId'], array(31, 99), true)
            || $routing['stageId'] !== 'C' . (int) $routing['categoryId'] . ':NEW'
            || !ctype_digit((string) $routing['assignedById']) || (int) $routing['assignedById'] < 1) {
            throw new RuntimeException('BITRIX_ROUTING_UNCONFIGURED');
        }
        $this->client = $client;
        $this->routing = $routing;
    }

    private function positiveId($value)
    {
        if ((!is_int($value) && !is_string($value)) || !ctype_digit((string) $value) || (int) $value < 1) {
            throw new RuntimeException('BITRIX_RESULT_UNCERTAIN');
        }
        return (int) $value;
    }

    public function deliver(array $input, IsiptoBitrix24Receipt $receipt)
    {
        $state = $receipt->read();
        if (!empty($state['pending'])) throw new RuntimeException('BITRIX_TRANSPORT_UNCERTAIN');
        $state['entity'] = 'deal';
        try {
            // A confirmed deal is only read again; it is never created a second time.
            if (empty($state['id'])) {
                if (empty($state['contactId'])) {
                    $found = $this->client->call('crm.duplicate.findbycomm', array(
                        'entity_type' => 'CONTACT', 'type' => 'PHONE', 'values' => array($input['phone']),
                    ));
                    $contacts = isset($found['CONTACT']) && is_array($found['CONTACT']) ? $found['CONTACT'] : array();
                    $contacts = array_values(array_filter($contacts, function ($v) {return ctype_digit((string) $v) && (int) $v > 0;}));
                    sort($contacts, SORT_NUMERIC);
                    if ($contacts) {
                        $state['contactId'] = (int) $contacts[0];
                    } else {
                        $contact = array('NAME' => $input['name'], 'ASSIGNED_BY_ID' => (int) $this->routing['assignedById'],
                            'PHONE' => array(array('VALUE' => $input['phone'], 'VALUE_TYPE' => 'WORK')));
                        if ($input['email'] !== '') $contact['EMAIL'] = array(array('VALUE' => $input['email'], 'VALUE_TYPE' => 'WORK'));
                        $state['time'] = time();$state['pending'] = true;$state['phase'] = 'contact';$receipt->write($state);
                        $state['contactId'] = $this->positiveId($this->client->call('crm.contact.add', array('fields' => $contact)));
                    }
                    $state['pending'] = false;$state['phase'] = 'contactReady';$receipt->write($state);
                }
                $fields = array(
                    'TITLE' => (isset($this->routing['title']) ? $this->routing['title'] : 'Заявка с сайта') . ' — ' . $input['name'],
                    'CATEGORY_ID' => (int) $this->routing['categoryId'], 'STAGE_ID' => $this->routing['stageId'],
                    'ASSIGNED_BY_ID' => (int) $this->routing['assignedById'],
                    'CONTACT_IDS' => array((int) $state['contactId']), 'SOURCE_ID' => 'WEB',
                    'COMMENTS' => 'Имя: ' . $input['name'] . "\nТелефон: " . $input['phone']
                        . ($input['email'] !== '' ? "\nEmail: " . $input['email'] : '')
                        . "\nСтраница: " . substr($input['page'], 0, 2048)
                        . "\nПричина обращения: " . substr($input['reason'], 0, 4096)
                        . (isset($input['details']) ? "\n" . substr($input['details'], 0, 8192) : ''),
                );
                $state['time'] = time();$state['pending'] = true;$state['phase'] = 'deal';$receipt->write($state);
                $state['id'] = $this->positiveId($this->client->call('crm.deal.add', array('fields' => $fields)));
                $state['pending'] = false;$state['phase'] = 'verify';$state['routingVerified'] = false;$receipt->write($state);
            }
            $actual = $this->client->call('crm.deal.get', array('id' => (int) $state['id']));
            if (!is_array($actual) || !isset($actual['ID'], $actual['CATEGORY_ID'], $actual['ASSIGNED_BY_ID'])
                || (int) $actual['ID'] !== (int) $state['id']
                || (int) $actual['CATEGORY_ID'] !== (int) $this->routing['categoryId']
                || (int) $actual['ASSIGNED_BY_ID'] !== (int) $this->routing['assignedById']) {
                throw new RuntimeException('BITRIX_ROUTING_MISMATCH');
            }
            $state['time'] = time();$state['routingVerified'] = true;$state['phase'] = 'accepted';$receipt->write($state);
            return (int) $state['id'];
        } catch (Exception $e) {
            // An explicit API rejection permits retry of that phase; an uncertain POST never does.
            if ($e->getMessage() === 'BITRIX_API_REJECTED') {
                $state['pending'] = false;$receipt->write($state);
            }
            throw $e;
        }
    }
}
