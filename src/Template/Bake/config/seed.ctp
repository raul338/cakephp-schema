<?php
// phpcs:ignoreFile
return [
<% foreach ($seedData as $tableName => $records): %>
    '<%= $tableName %>' => [
<%= $this->Schema->stringifyRecords($records) %>
    ],
<% endforeach; %>
];
