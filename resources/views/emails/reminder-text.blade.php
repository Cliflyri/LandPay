{{ html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '<br/>', '<br />'], ["\n\n", "\n", "\n", "\n"], $renderedBody))) }}

View and pay invoice: {{ $secureUrl }}
