<?php

$json = '[{"id":"1","name":"My First Quiz"},{"id":"2","name":"Another quiz"},{"id":"7","name":"Seventh quizzes"},{"id":8,"name":"AAAAA"}]';

echo '<pre>';
print_r(json_decode($json));
$json_decoded = json_decode($json);
echo findIndexInList($json_decoded, 2);
//unset($json_decoded[1]);
//print_r($json_decoded);
echo json_encode($json_decoded);
echo '</pre>';

function deleteFromList($id){
	// Without second 'true' arg, returns array of objects
	$list = json_decode(getRecord('quizzes'));
	$index = findIndexInList($list, $id);
	if (!empty($index)) {
		unset($list[$index]);
	}
	setRecord('quizzes', $list );
	unlink('data/' . $id . '.json');
}

// Finds the member of the index array that has a given ID property
// Assumes each $value member is a stdClass object
function findIndexInList($list, $id){
	foreach ($list as $index => $value) {
		if ($value->id == $id) {
			return $index;
		}
	}
	return false;
}