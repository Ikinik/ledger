<?php

list($ctrl, $method) = explode("/","expenses");

echo $ctrl;
echo $method;


SELECT moves.id, moves.cost, moves.date, points.lat, points.long, points.alt, moves.created, CONCAT("[", GROUP_CONCAT(CONCAT("{name: \"", types.name, ",\" id: ", types.id, "}")), "]") as types
FROM ledger.moves
left join ledger.types_moves on moves.id = types_moves.move_id
left join ledger.types on types.id = types_moves.type_id
left join ledger.points on points.id = moves.point_id
where moves.id = 113 AND moves.valid = 1
group by moves.id;
