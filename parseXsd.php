<?php
require 'vendor/autoload.php';
$GLOBALS['interfaces'] = [];

function loadXsd($filename, $includePath)
{
    $dom = new DOMDocument();
    $dom->load($filename);
    $imports = function (DOMElement $node) use ($includePath) {
        $namespace = $node->getAttribute('namespace');
        echo $includePath . '/' . $node->getAttribute('schemaLocation'), '->',$namespace,"\n";
    };
    $includes = function (DOMElement $node) use ($includePath) {
        $filename = $includePath . '/' . $node->getAttribute('schemaLocation');
        echo $filename,"\n";
        loadXsd($filename, $includePath);
    };
    $model = function (DOMElement $node) {
        eachXpath($node, 'xsd:attribute', function ($e) {
            $name = ucfirst($e->getAttribute('name'));
            $type = xsd2type($e->getAttribute('type'));
            if ($e->getAttribute('type') === 'xsd:QName' && substr($name, -3) === 'Ref') {
                $name = substr($name, 0, -3);
                $type = ucfirst($name);
            }
            //echo "    public function get" . $name . '():' . $type, "\n";
            $GLOBALS['interfaces'][count($GLOBALS['interfaces']) - 1]["methods"][] = [
                "type" => $e->getAttribute('type'),
                "name" => $e->getAttribute('name'),
            ];
        });
        eachXpath($node, 'xsd:sequence/xsd:element', function ($e) {
            $name = $e->getAttribute('name');
            $type = $e->getAttribute('ref');
            $minOccurs = $e->getAttribute('minOccurs');
            $maxOccurs = $e->getAttribute('maxOccurs');
            $multiple = $maxOccurs != "1";
            $name = $name ?: xsd2type($type);
            if ($e->getAttribute('type') === 'xsd:QName' && substr($name, -3) === 'Ref') {
                $name = substr($name, 0, -3);
            }
            $type = ucfirst($name);
            //echo "    public function get" . ucfirst($multiple ? plural($name) : $name) . '():' . $type, $multiple ? '[]' : '', "\n";
            $GLOBALS['interfaces'][count($GLOBALS['interfaces']) - 1]["methods"][] = [
                "type" => $e->getAttribute('ref'),
                "name" => $e->getAttribute('name'),
                "minOccurs" => $minOccurs,
                "maxOccurs" => $maxOccurs,
            ];
        });
    };
    $complexTypes = function (DOMElement $node) use (&$complexContent, &$model) {
        $name = substr($node->getAttribute('name'), 1);
        //echo "    '{$name}',\n";
        $GLOBALS['interfaces'][] = [
            "name" => $name,
            "extends" => [],
            "methods" => [],
        ];
        $model($node);
        eachXpath($node, 'xsd:complexContent', $complexContent);
    };
    $complexContent = function (DOMElement $node) use (&$model) {
        eachXpath($node, 'xsd:extension', function ($e) use ($model) {
            $parent = substr($e->getAttribute('base'), 1);
            //echo "  extends $parent\n";
            $GLOBALS['interfaces'][count($GLOBALS['interfaces']) - 1]["extends"][] = $parent;
            $model($e);
        });
    };
    eachXpath($dom, '//xsd:import', $imports);
    eachXpath($dom, '//xsd:include', $includes);
    eachXpath($dom, '//xsd:complexType', $complexTypes);
}
function xsd2type($type)
{
    $types = [
        'xsd:ID' => 'int',
        'xsd:string' => 'string',
        'xsd:anyURI' => 'string',
    ];
    return isset($types[$type]) ? $types[$type] : (strpos($type, ':')===false ? $type : explode(':', $type)[1]);
}
loadXsd('/Users/davidcallizaya/NetBeansProjects/xsd2php/BPMN20.xsd', '/Users/davidcallizaya/NetBeansProjects/xsd2php');
//print_r($GLOBALS['interfaces']);
//nano2component('BpmnInterface', $GLOBALS['interfaces'][0], 'output/components');
$enabled = [
//    'Activity',
//    'AdHocSubProcess',
//    'Artifact',
    'Assignment',
//    'Association',
//    'Auditing',
    'BaseElement',
//    'BaseElementWithMixedContent',
//    'BoundaryEvent',
//    'BusinessRuleTask',
    'CallableElement',
    'CallActivity',
//    'CallChoreography',
//    'CallConversation',
//    'CancelEventDefinition',
    'CatchEvent',
//    'Category',
//    'CategoryValue',
//    'Choreography',
//    'ChoreographyActivity',
//    'ChoreographyTask',
    'Collaboration',
//    'CompensateEventDefinition',
//    'ComplexBehaviorDefinition',
//    'ComplexGateway',
//    'ConditionalEventDefinition',
//    'Conversation',
//    'ConversationAssociation',
//    'ConversationLink',
//    'ConversationNode',
    'CorrelationKey',
//    'CorrelationProperty',
//    'CorrelationPropertyBinding',
//    'CorrelationPropertyRetrievalExpression',
//    'CorrelationSubscription',
    'DataAssociation',
    'DataInput',
    'DataInputAssociation',
//    'DataObject',
//    'DataObjectReference',
    'DataOutput',
    'DataOutputAssociation',
    'DataState',
//    'DataStore',
//    'DataStoreReference',
//    'Documentation',
//    'EndEvent',
//    'EndPoint',
//    'Error',
//    'ErrorEventDefinition',
//    'Escalation',
//    'EscalationEventDefinition',
    'Event',
//    'EventBasedGateway',
    'EventDefinition',
//    'ExclusiveGateway',
//    'Expression',
//    'Extension',
//    'ExtensionElements',
//    'FlowElement',
//    'FlowNode',
//    'FormalExpression',
//    'Gateway',
//    'GlobalBusinessRuleTask',
//    'GlobalChoreographyTask',
//    'GlobalConversation',
//    'GlobalManualTask',
//    'GlobalScriptTask',
//    'GlobalTask',
//    'GlobalUserTask',
//    'Group',
//    'HumanPerformer',
//    'ImplicitThrowEvent',
//    'InclusiveGateway',
    'InputSet',
//    'Interface',
    'IntermediateCatchEvent',
    'IntermediateThrowEvent',
//    'InputOutputBinding',
//    'InputOutputSpecification',
    'ItemDefinition',
//    'Lane',
//    'LaneSet',
//    'LinkEventDefinition',
//    'LoopCharacteristics',
//    'ManualTask',
    'Message',
    'MessageEventDefinition',
    'MessageFlow',
//    'MessageFlowAssociation',
//    'Monitoring',
//    'MultiInstanceLoopCharacteristics',
    'Operation',
    'OutputSet',
//    'ParallelGateway',
    'Participant',
//    'ParticipantAssociation',
//    'ParticipantMultiplicity',
//    'PartnerEntity',
//    'PartnerRole',
//    'Performer',
//    'PotentialOwner',
    'Process',
    'Property',
//    'ReceiveTask',
//    'Relationship',
//    'Rendering',
//    'Resource',
//    'ResourceAssignmentExpression',
//    'ResourceParameter',
//    'ResourceParameterBinding',
//    'ResourceRole',
    'RootElement',
//    'ScriptTask',
//    'Script',
//    'SendTask',
//    'SequenceFlow',
//    'ServiceTask',
//    'Signal',
//    'SignalEventDefinition',
//    'StandardLoopCharacteristics',
//    'StartEvent',
//    'SubChoreography',
//    'SubConversation',
    'SubProcess',
//    'Task',
//    'TerminateEventDefinition',
//    'TextAnnotation',
//    'Text',
    'ThrowEvent',
//    'TimerEventDefinition',
//    'Transaction',
//    'UserTask',
//    'Definitions',
//    'Import',
];
foreach ($GLOBALS['interfaces'] as $definition) {
    if (!in_array($definition['name'], $enabled)) continue;
    nano2component('BpmnInterface', $definition, 'output/components');
}

//nano2component('BpmnRepository', [
//    'name' => 'Event',
//    'types' => $GLOBALS['interfaces'],
//], 'output/components');
nano2component('BpmnRepository', ['name' => "Activity", 'types' => $GLOBALS['interfaces']], 'output/components');
die;
nano2component('BpmnRepository', ['name' => "Artifact", 'types' => $GLOBALS['interfaces']], 'output/components');
nano2component('BpmnRepository', ['name' => "ChoreographyActivity", 'types' => $GLOBALS['interfaces']], 'output/components');
nano2component('BpmnRepository', ['name' => "ConversationNode", 'types' => $GLOBALS['interfaces']], 'output/components');
nano2component('BpmnRepository', ['name' => "DataAssociation", 'types' => $GLOBALS['interfaces']], 'output/components');
nano2component('BpmnRepository', ['name' => "Event", 'types' => $GLOBALS['interfaces']], 'output/components');
nano2component('BpmnRepository', ['name' => "Gateway", 'types' => $GLOBALS['interfaces']], 'output/components');
nano2component('BpmnRepository', ['name' => "LoopCharacteristics", 'types' => $GLOBALS['interfaces']], 'output/components');
nano2component('BpmnRepository', ['name' => "RootElement", 'types' => $GLOBALS['interfaces']], 'output/components');
