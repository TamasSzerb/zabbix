<?php
/*
** ZABBIX
** Copyright (C) 2000-2008 SIA Zabbix
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
**/
?>
<?php
	global $TRANSLATION;

	$TRANSLATION=array(

	'S_DATE_FORMAT_YMDHMS'=>			'd M H:i:s',
	'S_DATE_FORMAT_YMD'=>			'd M Y',
	'S_HTML_CHARSET'=>			'UTF-8',
	'S_ACTIVATE_SELECTED'=>			'Ativar selecionados',
	'S_DISABLE_SELECTED'=>			'Desativar selecionadas',
	'S_DELETE_SELECTED'=>			'Remover selecionado',
	'S_COPY_SELECTED_TO'=>			'Copiar selecionados para ...',
	'S_HOST_IP'=>			'IP do Host',
	'S_SERVICE_TYPE'=>			'Tipo do Serviço',
	'S_SERVICE_PORT'=>			'Porta',
	'S_DISCOVERY_STATUS'=>			'Status auto busca',
	'S_RECEIVED_VALUE'=>			'Valor recebido',
	'S_UPTIME_DOWNTIME'=>			'Uptime/Downtime',
	'S_DISCOVERY_RULE'=>			'Regra de auto busca',
	'S_DISCOVERY'=>			'Auto busca',
	'S_DISCOVERY_BIG'=>			'AUTO BUSCA',
	'S_CONFIGURATION_OF_DISCOVERY'=>			'Configuração da auto busca',
	'S_CONFIGURATION_OF_DISCOVERY_BIG'=>			'CONFIGURAÇÃO DA AUTO BUSCA',
	'S_NO_DISCOVERY_RULES_DEFINED'=>			'Não há regras',
	'S_IP_RANGE'=>			'Intervalo de IPs',
	'S_CHECKS'=>			'Checagens',
	'S_CREATE_RULE'=>			'Criar Regra',
	'S_DELETE_RULE_Q'=>			'Remover regra?',
	'S_EVENT_SOURCE'=>			'Origem do evento',
	'S_NEW_CHECK'=>			'Nova checagem',
	'S_SSH'=>			'SSH',
	'S_LDAP'=>			'LDAP',
	'S_SMTP'=>			'SMTP',
	'S_FTP'=>			'FTP',
	'S_HTTP'=>			'HTTP',
	'S_POP'=>			'POP',
	'S_NNTP'=>			'NNTP',
	'S_IMAP'=>			'IMAP',
	'S_TCP'=>			'TCP',
	'S_PORTS_SMALL'=>			'portas',
	'S_DISCOVERY_RULES_DELETED'=>			'Regras de auto busca removidas',
	'S_DISCOVERY_RULE_DELETED'=>			'Regra de auto busca removida',
	'S_CANNOT_DELETE_DISCOVERY_RULE'=>			'Regra de auto busca não pode esr removida',
	'S_DISCOVERY_RULES_UPDATED'=>			'Regras de auto busca atualizadas',
	'S_DISCOVERY_RULE_UPDATED'=>			'Regra de auto busca atualizada',
	'S_CANNOT_UPDATE_DISCOVERY_RULE'=>			'Regra de auto busca não foi atualizada',
	'S_DISCOVERY_RULE_ADDED'=>			'Regra de auto busca adicionada',
	'S_CANNOT_ADD_DISCOVERY_RULE'=>			'Regra de auto busca não pode ser adicionada',
	'S_STATUS_OF_DISCOVERY_BIG'=>			'STATUS DA AUTO BUSCA',
	'S_STATUS_OF_DISCOVERY'=>			'Status da auto busca',
	'S_DETAILS_OF_SCENARIO'=>			'Detalhes do cenário',
	'S_DETAILS_OF_SCENARIO_BIG'=>			'DETALHES DO CENÁRIO',
	'S_SPEED'=>			'Velocidade',
	'S_RESPONSE_CODE'=>			'Código retornado',
	'S_TOTAL_BIG'=>			'TOTAL',
	'S_RESPONSE_TIME'=>			'Tempo de resposta',
	'S_IN_PROGRESS'=>			'Em andamento',
	'S_OF_SMALL'=>			'de',
	'S_IN_CHECK'=>			'Checando',
	'S_IDLE_TILL'=>			'Ocioso at',
	'S_FAILED_ON'=>			'Falhou em',
	'S_STATUS_OF_WEB_MONITORING'=>			'Status da monitoração Web',
	'S_STATUS_OF_WEB_MONITORING_BIG'=>			'STATUS DA MONITORAÇÃO WEB',
	'S_STATE'=>			'Status',
	'S_STATUS_CODES'=>			'Códigos de status',
	'S_WEB'=>			'Web',
	'S_CONFIGURATION_OF_WEB_MONITORING'=>			'Configuração do monitoramento Web',
	'S_CONFIGURATION_OF_WEB_MONITORING_BIG'=>			'CONFIGURAÇÃO DO MONITORAMENTO WEB',
	'S_SCENARIO'=>			'Cenário',
	'S_SCENARIOS'=>			'Cenários',
	'S_SCENARIOS_BIG'=>			'CENÁRIOS',
	'S_CREATE_SCENARIO'=>			'Criar cenário',
	'S_HIDE_DISABLED_SCENARIOS'=>			'Esconder cenários inativos',
	'S_SHOW_DISABLED_SCENARIOS'=>			'Mostrar cenários inativos',
	'S_NUMBER_OF_STEPS'=>			'Número de passos',
	'S_SCENARIO_DELETED'=>			'Cenário removido',
	'S_SCENARIO_ACTIVATED'=>			'Cenário ativado',
	'S_SCENARIO_DISABLED'=>			'Cenário desabilitado',
	'S_DELETE_SCENARIO_Q'=>			'Remover cenário?',
	'S_SCENARIO_UPDATED'=>			'Cenário atualizado',
	'S_CANNOT_UPDATE_SCENARIO'=>			'Cenário não foi atualizado',
	'S_SCENARIO_ADDED'=>			'Cenário adicionado',
	'S_CANNOT_ADD_SCENARIO'=>			'Cenário não foi incluído',
	'S_CANNOT_DELETE_SCENARIO'=>			'Cenário não foi removido',
	'S_AGENT'=>			'Agente',
	'S_VARIABLES'=>			'Variáveis',
	'S_STEP'=>			'Passo',
	'S_STEPS'=>			'Passos',
	'S_TIMEOUT'=>			'Timeout',
	'S_POST'=>			'Post',
	'S_REQUIRED'=>			'Requerido',
	'S_STEP_OF_SCENARIO'=>			'Passo do cenário',
	'S_ELEMENT'=>			'Elemento',
	'S_ELEMENTS'=>			'Elementos',
	'S_ONLY_HOST_INFO'=>			'Somente informação de host',
	'S_EXPORT_IMPORT'=>			'Exportar/Importar',
	'S_IMPORT_FILE'=>			'Importar arquivo',
	'S_IMPORT'=>			'Importar',
	'S_IMPORT_BIG'=>			'IMPORTAR',
	'S_EXPORT'=>			'Exportar',
	'S_EXPORT_BIG'=>			'EXPORTAR',
	'S_PREVIEW'=>			'Visualizar',
	'S_BACK'=>			'Atrás',
	'S_NO_DATA_FOR_EXPORT'=>			'Não há dados para exportar',
	'S_RULES'=>			'Regras',
	'S_EXISTING'=>			'Existente',
	'S_MISSING'=>			'Esperado',
	'S_REFRESH'=>			'Atualizar',
	'S_PREVIOUS'=>			'Anterior',
	'S_NEXT'=>			'Próximo',
	'S_RETRY'=>			'Tentar',
	'S_FINISH'=>			'Fim',
	'S_FAIL'=>			'Falhou',
	'S_UPDATE_BIG'=>			'ATUALIZAR',
	'S_INSTALLATION'=>			'Instalação',
	'S_NEW_INSTALLATION'=>			'Nova instalação',
	'S_NEW_INSTALLATION_BIG'=>			'NOVA INSTALAÇÃO',
	'S_INSTALLATION_UPDATE'=>			'Instalação/Atualização',
	'S_TIME_ZONE'=>			'Fuso horário',
	'S_DO_NOT_KEEP_HISTORY_OLDER_THAN'=>			'Não manter no histórico mais do que (dias)',
	'S_DO_NOT_KEEP_TRENDS_OLDER_THAN'=>			'Não arquivar trends mais do que (dias)',
	'S_MASTER_NODE'=>			'Nodo mestre',
	'S_CHILD'=>			'Remoto',
	'S_MASTER'=>			'Mestre',
	'S_NODE_UPDATED'=>			'Nodo atualizado',
	'S_CANNOT_UPDATE_NODE'=>			'Nodo não foi atualizado',
	'S_NODE_ADDED'=>			'Nodo adicionado',
	'S_CANNOT_ADD_NODE'=>			'Nodo não foi adicionado',
	'S_NODE_DELETED'=>			'Node removido',
	'S_CANNOT_DELETE_NODE'=>			'Não não foi removido',
	'S_CURRENT_NODE'=>			'Nodo atual',
	'S_ACKNOWLEDGES'=>			'Vistos',
	'S_ACKNOWLEDGE'=>			'Visto',
	'S_ACKNOWLEDGE_ALARM_BY'=>			'Alarme visto por',
	'S_ADD_COMMENT_BY'=>			'Comentário incluído por',
	'S_ALARM_ACKNOWLEDGES_BIG'=>			'VISTOS DADOS EM ALARMES',
	'S_ACKNOWLEDGE_ADDED'=>			'Visto incluído',
	'S_CONFIGURATION_OF_ACTIONS'=>			'Configuração de ações',
	'S_CONFIGURATION_OF_ACTIONS_BIG'=>			'CONFIGURAÇÃO DE AÇÕES',
	'S_OPERATION_TYPE'=>			'Tipo da operação',
	'S_SEND_MESSAGE'=>			'Enviar mensagem',
	'S_REMOTE_COMMAND'=>			'Comando remoto',
	'S_REMOTE_COMMANDS'=>			'Comandos remotos',
	'S_FILTER'=>			'Filtro',
	'S_TRIGGER_SEVERITY'=>			'Risco da trigger',
	'S_TRIGGER_VALUE'=>			'Valor da trigger',
	'S_TIME_PERIOD'=>			'Intervalo',
	'S_TRIGGER_DESCRIPTION'=>			'Descrição da trigger',
	'S_CONDITIONS'=>			'Condições',
	'S_CONDITION'=>			'Condição',
	'S_NEW_CONDITION'=>			'Nova condição',
	'S_OPERATIONS'=>			'Ações',
	'S_EDIT_OPERATION'=>			'Editar ação',
	'S_NO_CONDITIONS_DEFINED'=>			'Nenhuma condição',
	'S_ACTIONS_DELETED'=>			'Ações removidas',
	'S_CANNOT_DELETE_ACTIONS'=>			'Ações não podem ser removidas',
	'S_NO_OPERATIONS_DEFINED'=>			'Nenhuma ação',
	'S_NEW'=>			'Nova',
	'S_ADD_HOST'=>			'Adicionar servidor',
	'S_REMOVE_HOST'=>			'Remover servidor',
	'S_LINK_TO_TEMPLATE'=>			'Associar a template',
	'S_UNLINK_FROM_TEMPLATE'=>			'Desassociar da template',
	'S_INCORRECT_TRIGGER'=>			'Trigger incorreta',
	'S_INCORRECT_HOST'=>			'Servidor incorreto',
	'S_INCORRECT_PERIOD'=>			'Período incorreto',
	'S_INCORRECT_IP'=>			'IP incorreto',
	'S_INCORRECT_DISCOVERY_CHECK'=>			'Checagem de auto busca incorreta',
	'S_INCORRECT_PORT'=>			'Porta incorreta',
	'S_INCORRECT_DISCOVERY_STATUS'=>			'Status de auto busca incorreto',
	'S_INCORRECT_CONDITION_TYPE'=>			'Tipo de condição incorreta',
	'S_INCORRECT_OPERATION_TYPE'=>			'Tipo de operação incorreta',
	'S_INCORRECT_USER'=>			'Usuário incorreto',
	'S_ACTIONS'=>			'Ações',
	'S_ACTIONS_BIG'=>			'AÇÕES',
	'S_ACTION_ADDED'=>			'Ação incluída',
	'S_CANNOT_ADD_ACTION'=>			'Ação não foi adicionada',
	'S_ACTION_UPDATED'=>			'Ação atualizada',
	'S_CANNOT_UPDATE_ACTION'=>			'Ação não foi atualizada',
	'S_ACTION_DELETED'=>			'Ação removida',
	'S_CANNOT_DELETE_ACTION'=>			'Ação não foi removida',
	'S_SEND_MESSAGE_TO'=>			'Enviar mensagem para',
	'S_RUN_REMOTE_COMMANDS'=>			'Rodar comandos remotos',
	'S_DELAY'=>			'Espera',
	'S_SUBJECT'=>			'Assunto',
	'S_ON'=>			'ATIVO',
	'S_OFF'=>			'INATIVO',
	'S_NO_ACTIONS_DEFINED'=>			'Nenhuma ação definida',
	'S_SINGLE_USER'=>			'Usuário',
	'S_USER_GROUP'=>			'Grupo de usuários',
	'S_GROUP'=>			'Grupo',
	'S_USER'=>			'Usuário',
	'S_MESSAGE'=>			'Mensagem',
	'S_NOT_CLASSIFIED'=>			'Não classificada',
	'S_INFORMATION'=>			'Informação',
	'S_WARNING'=>			'Advertência',
	'S_AVERAGE'=>			'Médio',
	'S_HIGH'=>			'Alto',
	'S_DISASTER'=>			'Desastre',
	'S_AND_OR_BIG'=>			'E / OU',
	'S_AND_BIG'=>			'E',
	'S_AND'=>			'e',
	'S_OR_BIG'=>			'OU',
	'S_OR'=>			'ou',
	'S_TYPE_OF_CALCULATION'=>			'Tipo do cálculo',
	'S_CREATE_ACTION'=>			'Criar ação',
	'S_DELETE_SELECTED_ACTION_Q'=>			'Remover ação selecionada?',
	'S_LIKE_SMALL'=>			'como',
	'S_NOT_LIKE_SMALL'=>			'diferente',
	'S_IN_SMALL'=>			'em',
	'S_NOT_IN_SMALL'=>			'não em',
	'S_SHOW_ALL'=>			'Mostrar todos',
	'S_TIME'=>			'Data',
	'S_STATUS'=>			'Status',
	'S_DURATION'=>			'Duração',
	'S_TRUE_BIG'=>			'VERD',
	'S_FALSE_BIG'=>			'FALSO',
	'S_UNKNOWN_BIG'=>			'DESCONHECIDO',
	'S_TYPE'=>			'Tipo',
	'S_RECIPIENTS'=>			'Destinatário(s)',
	'S_ERROR'=>			'Erro',
	'S_SENT'=>			'enviado',
	'S_NOT_SENT'=>			'não enviado',
	'S_NO_ACTIONS_FOUND'=>			'Nenhuma ação encontrada',
	'S_CUSTOM_GRAPHS'=>			'Gráficos personalizados',
	'S_GRAPHS_BIG'=>			'GRÁFICOS',
	'S_SELECT_GRAPH_TO_DISPLAY'=>			'Selecione gráfico',
	'S_PERIOD'=>			'Período',
	'S_SELECT_GRAPH_DOT_DOT_DOT'=>			'Selecione gráfico...',
	'S_CANNNOT_UPDATE_VALUE_MAP'=>			'Mapeamento não foi atualizado',
	'S_VALUE_MAP_ADDED'=>			'Mapeamento de valor incluído',
	'S_CANNNOT_ADD_VALUE_MAP'=>			'Mapeamento de valor não foi incluído',
	'S_VALUE_MAP_DELETED'=>			'Mapeamento de valor removido',
	'S_CANNNOT_DELETE_VALUE_MAP'=>			'Mapeamento não foi removido',
	'S_VALUE_MAP_UPDATED'=>			'Mapeamento atualizado',
	'S_VALUE_MAPPING_BIG'=>			'MAPEAMENTO DE VALOR',
	'S_VALUE_MAPPING'=>			'Mapeamento de valor',
	'S_VALUE_MAP'=>			'Valores',
	'S_MAPPING'=>			'Mapeamento',
	'S_NEW_MAPPING'=>			'Novo mapeamento',
	'S_NO_MAPPING_DEFINED'=>			'Nenhum mapeamento definido',
	'S_CREATE_VALUE_MAP'=>			'Criar mapeamento de valor',
	'S_CONFIGURATION_OF_ZABBIX'=>			'Configuração do ZABBIX',
	'S_CONFIGURATION_OF_ZABBIX_BIG'=>			'CONFIGURAÇÃO DO ZABBIX',
	'S_CONFIGURATION_UPDATED'=>			'Configuração atualizada',
	'S_CONFIGURATION_WAS_NOT_UPDATED'=>			'Configuração não foi atualizada',
	'S_ADDED_NEW_MEDIA_TYPE'=>			'Adicionar novo tipo de mídia',
	'S_NEW_MEDIA_TYPE_WAS_NOT_ADDED'=>			'Nova mídia não foi adicionada',
	'S_MEDIA_TYPE_UPDATED'=>			'Mídia atualizada',
	'S_MEDIA_TYPE_WAS_NOT_UPDATED'=>			'Mídia não foi atualizada',
	'S_MEDIA_TYPE_DELETED'=>			'Mídia removida',
	'S_MEDIA_TYPE_WAS_NOT_DELETED'=>			'Mídia não foi removida',
	'S_CONFIGURATION'=>			'Configuração',
	'S_ADMINISTRATION'=>			'Administração',
	'S_DO_NOT_KEEP_ACTIONS_OLDER_THAN'=>			'Remover ações anteriores a (em dias)',
	'S_DO_NOT_KEEP_EVENTS_OLDER_THAN'=>			'Remover eventos anteriores a (em dias)',
	'S_NO_MEDIA_TYPES_DEFINED'=>			'Nenhum tipo de mídia definido',
	'S_SMTP_SERVER'=>			'Servidor SMTP',
	'S_SMTP_HELO'=>			'SMTP helo',
	'S_SMTP_EMAIL'=>			'SMTP email',
	'S_SCRIPT_NAME'=>			'Nome script',
	'S_DELETE_SELECTED_MEDIA'=>			'Remover mídia selecionada?',
	'S_DELETE_SELECTED_IMAGE'=>			'Remover imagem selecionada?',
	'S_HOUSEKEEPER'=>			'Limpeza',
	'S_MEDIA_TYPES'=>			'Tipos de mídias',
	'S_ESCALATION_RULES'=>			'Regras de escalonamento',
	'S_ESCALATION'=>			'Escalonamento',
	'S_ESCALATION_RULE'=>			'Regra de escalonamento',
	'S_DEFAULT'=>			'Default',
	'S_IMAGES'=>			'Imagens',
	'S_IMAGE'=>			'Imagem',
	'S_IMAGES_BIG'=>			'IMAGENS',
	'S_ICON'=>			'Ícone',
	'S_NO_IMAGES_DEFINED'=>			'Não há imagens definidas',
	'S_BACKGROUND'=>			'Fundo',
	'S_UPLOAD'=>			'Enviar',
	'S_IMAGE_ADDED'=>			'Imagem incluída',
	'S_CANNOT_ADD_IMAGE'=>			'Imagem não foi incluída',
	'S_IMAGE_DELETED'=>			'Imagem removida',
	'S_CANNOT_DELETE_IMAGE'=>			'Imagem não foi removida',
	'S_IMAGE_UPDATED'=>			'Imagem atualizada',
	'S_CANNOT_UPDATE_IMAGE'=>			'Imagem não foi atualizada',
	'S_OTHER'=>			'Outro',
	'S_OTHER_PARAMETERS'=>			'Outros parâmetros',
	'S_REFRESH_UNSUPPORTED_ITEMS'=>			'Atualizar itens não suportados (em segundos)',
	'S_CREATE_MEDIA_TYPE'=>			'Criar Mídia',
	'S_CREATE_IMAGE'=>			'Criar Imagem',
	'S_WORKING_TIME'=>			'Horário comercial',
	'S_USER_GROUP_FOR_DATABASE_DOWN_MESSAGE'=>			'Grupo de usuários que receberá a mensagem de BD indisponível',
	'S_INCORRECT_GROUP'=>			'Grupo incorreto',
	'S_NOTHING_TO_DO'=>			'Nada a ser feito',
	'S_INCORRECT_WORK_PERIOD'=>			'Período de trabalho incorreto',
	'S_NODE'=>			'Nodo',
	'S_NODES'=>			'Nodos',
	'S_NODES_BIG'=>			'NODOS',
	'S_NEW_NODE'=>			'Novo nodo',
	'S_NO_NODES_DEFINED'=>			'Não há nodos',
	'S_NO_PERMISSIONS'=>			'Não tem permissão!',
	'S_LATEST_DATA_BIG'=>			'DADOS RECENTES',
	'S_ALL_SMALL'=>			'todos',
	'S_MINUS_ALL_MINUS'=>			'- todos -',
	'S_MINUS_OTHER_MINUS'=>			'- outro -',
	'S_GRAPH'=>			'Gráfico',
	'S_CONNECTED_AS'=>			'Conectado como',
	'S_SIA_ZABBIX'=>			'',
	'S_ITEM_ADDED'=>			'Item incluído',
	'S_ITEM_UPDATED'=>			'Item atualizada',
	'S_ITEMS_UPDATED'=>			'Ítens atualizados',
	'S_PARAMETER'=>			'Parâmetro',
	'S_COLOR'=>			'Cor',
	'S_UP'=>			'Acima',
	'S_DOWN'=>			'Abaixo',
	'S_NEW_ITEM_FOR_THE_GRAPH'=>			'Novo item de gráfico',
	'S_SORT_ORDER_0_100'=>			'Posição (0->100)',
	'S_YAXIS_SIDE'=>			'Eixo Y',
	'S_LEFT'=>			'Esquerda',
	'S_FUNCTION'=>			'Função',
	'S_MIN_SMALL'=>			'min',
	'S_AVG_SMALL'=>			'med',
	'S_MAX_SMALL'=>			'max',
	'S_DRAW_STYLE'=>			'Estilo',
	'S_SIMPLE'=>			'Simples',
	'S_GRAPH_TYPE'=>			'Tipo do gráfico',
	'S_STACKED'=>			'Pilha',
	'S_NORMAL'=>			'Normal',
	'S_AGGREGATED'=>			'Agregada',
	'S_AGGREGATED_PERIODS_COUNT'=>			'Intervalo de tempo (agragado)',
	'S_CONFIGURATION_OF_GRAPHS'=>			'Configuração de gráficos',
	'S_CONFIGURATION_OF_GRAPHS_BIG'=>			'CONFIGURAÇÃO DE GRÁFICOS',
	'S_GRAPH_ADDED'=>			'Gráfico incluído',
	'S_GRAPH_UPDATED'=>			'Gráfico atualizado',
	'S_CANNOT_UPDATE_GRAPH'=>			'Gráfico não foi atualizado',
	'S_GRAPH_DELETED'=>			'Gráfico removido',
	'S_CANNOT_DELETE_GRAPH'=>			'Gráfico não foi removido',
	'S_CANNOT_ADD_GRAPH'=>			'Gráfico não foi adicionado',
	'S_ID'=>			'Id',
	'S_NO_GRAPHS_DEFINED'=>			'Nenhum gráfico',
	'S_NO_GRAPH_DEFINED'=>			'Nenhum gráfico',
	'S_DELETE_GRAPH_Q'=>			'Remover gráfico?',
	'S_YAXIS_MIN_VALUE'=>			'Valor MÍNIMO no eixo Y',
	'S_YAXIS_MAX_VALUE'=>			'Valor MÁXIMO no eixo Y',
	'S_CALCULATED'=>			'Calculado',
	'S_FIXED'=>			'Fixo',
	'S_CREATE_GRAPH'=>			'Criar Gráfico',
	'S_SHOW_WORKING_TIME'=>			'Mostrar horário comercial',
	'S_SHOW_TRIGGERS'=>			'Mostrar triggers',
	'S_GRAPH_ITEM'=>			'Item do gráfico',
	'S_REQUIRED_ITEMS_FOR_GRAPH'=>			'Ítens obrigatórios para gráficos',
	'S_LAST_HOUR_GRAPH'=>			'Gráfico última hora',
	'S_LAST_WEEK_GRAPH'=>			'Gráfico última semana',
	'S_LAST_MONTH_GRAPH'=>			'Gráfico último mês',
	'S_500_LATEST_VALUES'=>			'Últimos 500 valores',
	'S_TIMESTAMP'=>			'Hora',
	'S_LOCAL'=>			'Local',
	'S_SOURCE'=>			'Origem',
	'S_SHOW_SELECTED'=>			'Mostrar selecionados',
	'S_HIDE_SELECTED'=>			'Esconder selecionados',
	'S_MARK_SELECTED'=>			'Marcar selecionados',
	'S_MARK_OTHERS'=>			'Marcar outros',
	'S_AS_RED'=>			'em Vermelho',
	'S_AS_GREEN'=>			'em Verde',
	'S_AS_BLUE'=>			'em Azul',
	'S_APPLICATION'=>			'Aplicação',
	'S_APPLICATIONS'=>			'Aplicações',
	'S_APPLICATIONS_BIG'=>			'APPLICAÇÃO',
	'S_CREATE_APPLICATION'=>			'Criar aplicação',
	'S_ACTIVATE_ITEMS'=>			'Ativar Ítens',
	'S_DISABLE_ITEMS'=>			'Desabilitar Ítens',
	'S_APPLICATION_UPDATED'=>			'Aplicação atualizada',
	'S_CANNOT_UPDATE_APPLICATION'=>			'Aplicação não foi atualizada',
	'S_APPLICATION_ADDED'=>			'Aplicação incluída',
	'S_CANNOT_ADD_APPLICATION'=>			'Aplicação não foi incluída',
	'S_APPLICATION_DELETED'=>			'Aplicação removida',
	'S_CANNOT_DELETE_APPLICATION'=>			'Aplicação não foi removida',
	'S_NO_APPLICATIONS_DEFINED'=>			'Nenhuma aplicação definida',
	'S_HOSTS'=>			'Hosts',
	'S_ITEMS'=>			'Ítens',
	'S_ITEMS_BIG'=>			'ITEMS',
	'S_TRIGGERS'=>			'Triggers',
	'S_GRAPHS'=>			'Gráficos',
	'S_HOST_ADDED'=>			'Host incluído',
	'S_CANNOT_ADD_HOST'=>			'Host não foi incluído',
	'S_HOST_UPDATED'=>			'Host atualizado',
	'S_CANNOT_UPDATE_HOST'=>			'Host não foi atualizado',
	'S_HOST_STATUS_UPDATED'=>			'Status do host atualizado',
	'S_CANNOT_UPDATE_HOST_STATUS'=>			'Status do host não foi atualizado',
	'S_HOST_DELETED'=>			'Host removido',
	'S_CANNOT_DELETE_HOST'=>			'Host não foi removido',
	'S_HOST_GROUPS_BIG'=>			'GRUPOS DE HOSTS',
	'S_NO_HOST_GROUPS_DEFINED'=>			'Nenhum grupo definido',
	'S_NO_HOSTS_DEFINED'=>			'Nenhum host',
	'S_NO_TEMPLATES_DEFINED'=>			'Nenhuma templates',
	'S_HOSTS_BIG'=>			'HOSTS',
	'S_HOST'=>			'Host',
	'S_CONNECT_TO'=>			'Connectado a',
	'S_DNS'=>			'DNS',
	'S_IP'=>			'IP',
	'S_PORT'=>			'Porta',
	'S_MONITORED'=>			'Monitorado',
	'S_NOT_MONITORED'=>			'Não monitorado',
	'S_TEMPLATE'=>			'Template',
	'S_DELETED'=>			'Removido',
	'S_UNKNOWN'=>			'Desconhecido',
	'S_GROUPS'=>			'Grupos',
	'S_NO_GROUPS_DEFINED'=>			'Nenhum grupo definido',
	'S_NEW_GROUP'=>			'Novo grupo',
	'S_DNS_NAME'=>			'Nome DNS',
	'S_IP_ADDRESS'=>			'Endereço IP',
	'S_LINK_WITH_TEMPLATE'=>			'Associar a Template',
	'S_USE_PROFILE'=>			'Usar inventário',
	'S_DELETE_SELECTED_HOST_Q'=>			'Remover host selecionado?',
	'S_DELETE_SELECTED_WITH_LINKED_ELEMENTS'=>			'Remover selecionado e elementos associados',
	'S_GROUP_NAME'=>			'Nome do Grupo',
	'S_HOST_GROUP'=>			'Grupo de Hosts',
	'S_HOST_GROUPS'=>			'Grupos de Hosts',
	'S_UPDATE'=>			'Atualiza',
	'S_AVAILABILITY'=>			'Disponibilidade',
	'S_AVAILABLE'=>			'Disponível',
	'S_NOT_AVAILABLE'=>			'Indisponível',
	'S_HOST_PROFILE'=>			'Inventário do host',
	'S_DEVICE_TYPE'=>			'Tipo de dispositivo',
	'S_OS'=>			'S.O.',
	'S_SERIALNO'=>			'Número de série',
	'S_TAG'=>			'Tag',
	'S_HARDWARE'=>			'Hardware',
	'S_SOFTWARE'=>			'Software',
	'S_CONTACT'=>			'Contatato',
	'S_LOCATION'=>			'Local',
	'S_NOTES'=>			'Notas',
	'S_MACADDRESS'=>			'Endereço MAC',
	'S_ADD_TO_GROUP'=>			'Adicionar ao grupo',
	'S_DELETE_FROM_GROUP'=>			'Remover do grupo',
	'S_UPDATE_IN_GROUP'=>			'Atualizar no grupo',
	'S_DELETE_SELECTED_HOSTS_Q'=>			'Remover hosts selecionados?',
	'S_CREATE_HOST'=>			'Criar Host',
	'S_CREATE_TEMPLATE'=>			'Criar Template',
	'S_TEMPLATE_LINKAGE'=>			'Associação a templates',
	'S_TEMPLATES'=>			'Templates',
	'S_TEMPLATES_BIG'=>			'TEMPLATES',
	'S_UNLINK'=>			'Desassociar',
	'S_UNLINK_AND_CLEAR'=>			'Desassociar e limpar',
	'S_NO_ITEMS_DEFINED'=>			'Nenhum item definido',
	'S_NO_ITEM_DEFINED'=>			'Nenhum item  definido',
	'S_HISTORY_CLEARED'=>			'Histórico zerado',
	'S_CLEAR_HISTORY_FOR_SELECTED'=>			'Zerar histórico dos ítens selecionados',
	'S_CLEAR_HISTORY'=>			'Zerar histórico',
	'S_CANNOT_CLEAR_HISTORY'=>			'Histórico não foi zerado',
	'S_CONFIGURATION_OF_ITEMS'=>			'Configuração de ítens',
	'S_CONFIGURATION_OF_ITEMS_BIG'=>			'CONFIGURAÇÃO DE ÍTENS',
	'S_CANNOT_UPDATE_ITEM'=>			'Item não foi atualizado',
	'S_STATUS_UPDATED'=>			'Status atualizado',
	'S_CANNOT_UPDATE_STATUS'=>			'Status não foi atualizado',
	'S_CANNOT_ADD_ITEM'=>			'Item não foi adicionado',
	'S_ITEM_DELETED'=>			'Item removido',
	'S_CANNOT_DELETE_ITEM'=>			'Item não foi removido',
	'S_ITEMS_DELETED'=>			'Ítens removidos',
	'S_CANNOT_DELETE_ITEMS'=>			'Ítens não foram removidos',
	'S_ITEMS_ACTIVATED'=>			'Ítens ativados',
	'S_ITEMS_DISABLED'=>			'Ítens desativados',
	'S_KEY'=>			'Chave',
	'S_DESCRIPTION'=>			'Descrição',
	'S_UPDATE_INTERVAL'=>			'Intervalo de atualização',
	'S_HISTORY'=>			'Histórico',
	'S_TRENDS'=>			'Estatísticas',
	'S_ZABBIX_AGENT'=>			'Agente ZABBIX',
	'S_ZABBIX_AGENT_ACTIVE'=>			'Agente ZABBIX (ativo)',
	'S_SNMPV1_AGENT'=>			'Agente SNMPv1',
	'S_ZABBIX_TRAPPER'=>			'ZABBIX trapper',
	'S_SIMPLE_CHECK'=>			'Monitoração simples',
	'S_SNMPV2_AGENT'=>			'Agente SNMPv2',
	'S_SNMPV3_AGENT'=>			'Agente SNMPv3',
	'S_ZABBIX_INTERNAL'=>			'ZABBIX interno',
	'S_ZABBIX_AGGREGATE'=>			'ZABBIX agregado',
	'S_EXTERNAL_CHECK'=>			'Monitoramento externo',
	'S_WEB_MONITORING'=>			'Monitoramento Web',
	'S_ACTIVE'=>			'Ativo',
	'S_NOT_SUPPORTED'=>			'Não suportado',
	'S_EMAIL'=>			'Email',
	'S_JABBER'=>			'Jabber',
	'S_JABBER_IDENTIFIER'=>			'Identificador Jabber',
	'S_SMS'=>			'SMS',
	'S_SCRIPT'=>			'Script',
	'S_GSM_MODEM'=>			'Modem GSM',
	'S_UNITS'=>			'Unidades',
	'S_UPDATE_INTERVAL_IN_SEC'=>			'Intervalo atualização (em seg)',
	'S_KEEP_HISTORY_IN_DAYS'=>			'Manter histórico (em dias)',
	'S_KEEP_TRENDS_IN_DAYS'=>			'Manter estatísticas (em dias)',
	'S_TYPE_OF_INFORMATION'=>			'Tipo de informação',
	'S_STORE_VALUE'=>			'Armazenar valor',
	'S_SHOW_VALUE'=>			'Mostrar valor',
	'S_NUMERIC_UNSIGNED'=>			'Numérico (inteiro 64bits)',
	'S_NUMERIC_FLOAT'=>			'Numérico (float)',
	'S_CHARACTER'=>			'Carácter',
	'S_LOG'=>			'Log',
	'S_TEXT'=>			'Texto',
	'S_AS_IS'=>			'Sem alterar',
	'S_DELTA_SPEED_PER_SECOND'=>			'Delta (alterações/seg)',
	'S_DELTA_SIMPLE_CHANGE'=>			'Delta (alterações simples)',
	'S_ITEM'=>			'Item',
	'S_SNMP_COMMUNITY'=>			'SNMP community',
	'S_SNMP_OID'=>			'SNMP OID',
	'S_SNMP_PORT'=>			'SNMP porta',
	'S_ALLOWED_HOSTS'=>			'Hosts permitidos',
	'S_SNMPV3_SECURITY_NAME'=>			'SNMPv3 security name',
	'S_SNMPV3_SECURITY_LEVEL'=>			'SNMPv3 security level',
	'S_SNMPV3_AUTH_PASSPHRASE'=>			'SNMPv3 auth passphrase',
	'S_SNMPV3_PRIV_PASSPHRASE'=>			'SNMPv3 priv passphrase',
	'S_CUSTOM_MULTIPLIER'=>			'Multiplicador',
	'S_DO_NOT_USE'=>			'Não usar',
	'S_USE_MULTIPLIER'=>			'Usar multiplicador',
	'S_SELECT_HOST_DOT_DOT_DOT'=>			'Selecione host...',
	'S_LOG_TIME_FORMAT'=>			'Formato da data no log',
	'S_CREATE_ITEM'=>			'Criar Item',
	'S_X_ELEMENTS_COPY_TO_DOT_DOT_DOT'=>			'elementos a copiar para ...',
	'S_MODE'=>			'Modo',
	'S_TARGET'=>			'Destino',
	'S_TARGET_TYPE'=>			'Tipo do destino',
	'S_SKIP_EXISTING_ITEMS'=>			'Ignorar ítens existentes',
	'S_UPDATE_EXISTING_NON_LINKED_ITEMS'=>			'atualizar ítens não associados',
	'S_COPY'=>			'Copiar',
	'S_SHOW_ITEMS_WITH_DESCRIPTION_LIKE'=>			'Mostrar ítens com a descrição',
	'S_HISTORY_CLEARING_CAN_TAKE_A_LONG_TIME_CONTINUE_Q'=>			'Zerar o histórico pode levar um tempo bem longo. Continuar?',
	'S_MASS_UPDATE'=>			'Atualização em massa',
	'S_SEARCH'=>			'Pesquisar',
	'S_ORIGINAL'=>			'Original',
	'S_NEW_FLEXIBLE_INTERVAL'=>			'Novo intervalo flexível',
	'S_FLEXIBLE_INTERVALS'=>			'Intervalos flexíveis (seg)',
	'S_LATEST_EVENTS'=>			'Últimos eventos',
	'S_HISTORY_OF_EVENTS_BIG'=>			'HISTÓRICO DE EVENTOS',
	'S_NO_EVENTS_FOUND'=>			'Nenhum evento encontrado',
	'S_LAST_CHECK'=>			'Última checagem',
	'S_LAST_VALUE'=>			'Último valor',
	'S_LINK'=>			'Link',
	'S_LABEL'=>			'Texto',
	'S_X'=>			'X',
	'S_Y'=>			'Y',
	'S_ICON_UNKNOWN'=>			'Ícone (desconhecido)',
	'S_LINK_STATUS_INDICATOR'=>			'Indicador do estado do link',
	'S_CONFIGURATION_OF_NETWORK_MAPS'=>			'CONFIGURAÇÃO DE MAPAS DE REDE',
	'S_MAPS_BIG'=>			'MAPAS',
	'S_NO_MAPS_DEFINED'=>			'Nenhum mapa definido',
	'S_CREATE_MAP'=>			'Criar mapa',
	'S_ICON_LABEL_LOCATION'=>			'Localização do texto do ícone',
	'S_BOTTOM'=>			'Abaixo',
	'S_TOP'=>			'Topo',
	'S_OK_BIG'=>			'OK',
	'S_ZABBIX_URL'=>			'http://www.zabbix.com',
	'S_NETWORK_MAPS'=>			'Mapas de rede',
	'S_NETWORK_MAPS_BIG'=>			'MAPAS DE REDE',
	'S_BACKGROUND_IMAGE'=>			'Imagem de fundo',
	'S_ICON_LABEL_TYPE'=>			'Tipo do texto do ícone',
	'S_LABEL_LOCATION'=>			'Texto de localização',
	'S_ELEMENT_NAME'=>			'Nome do elemento',
	'S_STATUS_ONLY'=>			'Somente status',
	'S_NOTHING'=>			'Nada',
	'S_CONFIGURATION_OF_MEDIA_TYPES_BIG'=>			'CONFIGURAÇÃO DE TIPOS DE MÍDIA',
	'S_MEDIA'=>			'Mídia',
	'S_SEND_TO'=>			'Enviar para',
	'S_WHEN_ACTIVE'=>			'Quando ativo',
	'S_NO_MEDIA_DEFINED'=>			'Não há mídia definida',
	'S_NEW_MEDIA'=>			'Nova mídia',
	'S_USE_IF_SEVERITY'=>			'Usar se risco',
	'S_SAVE'=>			'Salvar',
	'S_CANCEL'=>			'Cancelar',
	'S_OVERVIEW'=>			'Visão geral',
	'S_OVERVIEW_BIG'=>			'VISÃO GERAL',
	'S_DATA'=>			'Dados',
	'S_SHOW_GRAPH_OF_ITEM'=>			'Ver gráfico do ítem',
	'S_SHOW_VALUES_OF_ITEM'=>			'Ver valores do ítem',
	'S_VALUES'=>			'Valores',
	'S_5_MIN'=>			'5 min',
	'S_15_MIN'=>			'15 min',
	'S_QUEUE_BIG'=>			'FILA',
	'S_QUEUE_OF_ITEMS_TO_BE_UPDATED_BIG'=>			'FILA DE ÍTENS A SER ATUALIZADA MUITO GRANDE',
	'S_NEXT_CHECK'=>			'Próxima checagem',
	'S_THE_QUEUE_IS_EMPTY'=>			'A fila está vazia',
	'S_TOTAL'=>			'Total',
	'S_COUNT'=>			'Contador',
	'S_5_SECONDS'=>			'5 segundos',
	'S_10_SECONDS'=>			'10 segundos',
	'S_30_SECONDS'=>			'30 segundos',
	'S_1_MINUTE'=>			'1 minutos',
	'S_5_MINUTES'=>			'5 minutos',
	'S_STATUS_OF_ZABBIX'=>			'Status do ZABBIX',
	'S_STATUS_OF_ZABBIX_BIG'=>			'STATUS DO ZABBIX',
	'S_VALUE'=>			'Valor',
	'S_ZABBIX_SERVER_IS_RUNNING'=>			'ZABBIX está rodando',
	'S_VALUES_STORED'=>			'Valores armazenados',
	'S_TRENDS_STORED'=>			'Estatísticas armazenadas',
	'S_NUMBER_OF_EVENTS'=>			'Número de eventos',
	'S_NUMBER_OF_ALERTS'=>			'Número de alertas',
	'S_NUMBER_OF_TRIGGERS'=>			'Número de triggers (ativas/desativadas)[verdadeiro/desconhecido/falso]',
	'S_NUMBER_OF_TRIGGERS_SHORT'=>			'Triggers (a/d)[v/d/f]',
	'S_NUMBER_OF_ITEMS'=>			'Número de ítens (monitorados/desativados/não suportado)[trapper]',
	'S_NUMBER_OF_ITEMS_SHORT'=>			'Ítens (m/d/n)[t]',
	'S_NUMBER_OF_USERS'=>			'Número de usuários (online)',
	'S_NUMBER_OF_USERS_SHORT'=>			'Usuários (online)',
	'S_NUMBER_OF_HOSTS'=>			'Número de hosts (monitored/not monitored/templates/removido)',
	'S_NUMBER_OF_HOSTS_SHORT'=>			'Hosts (m/n/t/d)',
	'S_YES'=>			'Sim',
	'S_NO'=>			'Não',
	'S_RUNNING'=>			'rodando',
	'S_NOT_RUNNING'=>			'parado',
	'S_AVAILABILITY_REPORT'=>			'Relatório de disponibilidade',
	'S_AVAILABILITY_REPORT_BIG'=>			'RELATÓRIO DE DISPONIBILIDADE',
	'S_SHOW'=>			'Mostrar',
	'S_IT_SERVICES_AVAILABILITY_REPORT'=>			'Relatório de dispnibilidade dos serviços IT',
	'S_IT_SERVICES_AVAILABILITY_REPORT_BIG'=>			'RELATÓRIO DE DISPONIBILIDADE DOS SERVIÇOS IT',
	'S_FROM'=>			'De',
	'S_FROM_SMALL'=>			'de',
	'S_TILL'=>			'At',
	'S_OK'=>			'Ok',
	'S_PROBLEMS'=>			'Problemas',
	'S_PERCENTAGE'=>			'Porcentagem',
	'S_SLA'=>			'SLA',
	'S_DAY'=>			'Dia',
	'S_MONTH'=>			'Mês',
	'S_YEAR'=>			'Ano',
	'S_DAILY'=>			'Diário',
	'S_WEEKLY'=>			'Semanal',
	'S_MONTHLY'=>			'Mensal',
	'S_YEARLY'=>			'Anual',
	'S_NOTIFICATIONS'=>			'Notificações',
	'S_NOTIFICATIONS_BIG'=>			'NOTIFICAÇÕES',
	'S_IT_NOTIFICATIONS'=>			'Relatório de notificações',
	'S_TRIGGERS_TOP_100'=>			'Triggers mais ativadas - top 100',
	'S_TRIGGERS_TOP_100_BIG'=>			'TRIGGERS MAIS ATIVADAS - TOP 100',
	'S_NUMBER_OF_STATUS_CHANGES'=>			'Alterações no status',
	'S_WEEK'=>			'Semana',
	'S_LAST'=>			'Última',
	'S_SCREENS'=>			'Telas',
	'S_SCREEN'=>			'Tela',
	'S_CONFIGURATION_OF_SCREENS_BIG'=>			'CONFIGURAÇÃO DE TELAS',
	'S_CONFIGURATION_OF_SCREENS'=>			'Configuração de telas',
	'S_SCREEN_ADDED'=>			'Tela incluída',
	'S_CANNOT_ADD_SCREEN'=>			'Tela não foi incluída',
	'S_SCREEN_UPDATED'=>			'Tela atualizada',
	'S_CANNOT_UPDATE_SCREEN'=>			'Tela não foi atualizada',
	'S_SCREEN_DELETED'=>			'Tela removida',
	'S_CANNOT_DELETE_SCREEN'=>			'Tela não foi removida',
	'S_COLUMNS'=>			'Colunas',
	'S_ROWS'=>			'Linhas',
	'S_NO_SCREENS_DEFINED'=>			'Nenhuma tela definida',
	'S_DELETE_SCREEN_Q'=>			'Remover tela?',
	'S_CONFIGURATION_OF_SCREEN_BIG'=>			'CONFIGURAÇÃO DE TELA',
	'S_SCREEN_CELL_CONFIGURATION'=>			'Tela de configuração para celular',
	'S_RESOURCE'=>			'Recurso',
	'S_RIGHTS_OF_RESOURCES'=>			'Direitos do usuário',
	'S_NO_RESOURCES_DEFINED'=>			'Nenhum recurso definido',
	'S_SIMPLE_GRAPH'=>			'Gráfico simples',
	'S_GRAPH_NAME'=>			'Nome do Gráfico',
	'S_WIDTH'=>			'Largura',
	'S_HEIGHT'=>			'Altura',
	'S_CREATE_SCREEN'=>			'Criar Tela',
	'S_EDIT'=>			'Editar',
	'S_DIMENSION_COLS_ROWS'=>			'Dimensão (cols x linhas)',
	'S_SLIDESHOWS'=>			'Slideshows',
	'S_SLIDESHOW'=>			'Slideshow',
	'S_CONFIGURATION_OF_SLIDESHOWS_BIG'=>			'CONFIGURAÇÃO DE SLIDESHOWS',
	'S_SLIDESHOWS_BIG'=>			'SLIDESHOWS',
	'S_NO_SLIDESHOWS_DEFINED'=>			'Nenhum slideshow definido',
	'S_COUNT_OF_SLIDES'=>			'Número de slides',
	'S_NO_SLIDES_DEFINED'=>			'Nenhum slides definido',
	'S_SLIDES'=>			'Slides',
	'S_NEW_SLIDE'=>			'Novo slide',
	'S_MAP'=>			'Mapa',
	'S_AS_PLAIN_TEXT'=>			'Como texto puro',
	'S_PLAIN_TEXT'=>			'Texto puro',
	'S_COLUMN_SPAN'=>			'Sobrepor coluna',
	'S_ROW_SPAN'=>			'Sobrepor linha',
	'S_SHOW_LINES'=>			'Mostrar linhas',
	'S_HOSTS_INFO'=>			'Info hosts',
	'S_TRIGGERS_INFO'=>			'Info triggers',
	'S_SERVER_INFO'=>			'Info servidor',
	'S_CLOCK'=>			'Relógio',
	'S_TRIGGERS_OVERVIEW'=>			'Visão geral triggers',
	'S_DATA_OVERVIEW'=>			'Visão geral dados',
	'S_HISTORY_OF_ACTIONS'=>			'Histórico de ações',
	'S_HISTORY_OF_EVENTS'=>			'Histórico de eventos',
	'S_TIME_TYPE'=>			'Tipo de horário',
	'S_SERVER_TIME'=>			'Hora do servidor',
	'S_LOCAL_TIME'=>			'Hora local',
	'S_STYLE'=>			'Estilo',
	'S_VERTICAL'=>			'Vertical',
	'S_HORIZONTAL'=>			'Horizontal',
	'S_HORIZONTAL_ALIGN'=>			'Alinhamento horizontal',
	'S_CENTRE'=>			'Centro',
	'S_RIGHT'=>			'Direita',
	'S_VERTICAL_ALIGN'=>			'Alinhamento vertical',
	'S_MIDDLE'=>			'Centro',
	'S_CUSTOM_SCREENS'=>			'Telas personalizadas',
	'S_SCREENS_BIG'=>			'TELAS',
	'S_SLIDESHOW_UPDATED'=>			'Slideshow atualizado',
	'S_CANNOT_UPDATE_SLIDESHOW'=>			'Slideshow não foi atualizado',
	'S_SLIDESHOW_ADDED'=>			'Slideshow incluído',
	'S_CANNOT_ADD_SLIDESHOW'=>			'Slideshow não foi incluído',
	'S_SLIDESHOW_DELETED'=>			'Slideshow removido',
	'S_CANNOT_DELETE_SLIDESHOW'=>			'Slideshow não foi removido',
	'S_DELETE_SLIDESHOW_Q'=>			'Remover slideshow?',
	'S_ROOT_SMALL'=>			'raiz',
	'S_IT_SERVICE'=>			'Serviço IT',
	'S_IT_SERVICES'=>			'Serviços IT',
	'S_SERVICE_UPDATED'=>			'Serviços atualizados',
	'S_NO_IT_SERVICE_DEFINED'=>			'Nenhum serviço',
	'S_CANNOT_UPDATE_SERVICE'=>			'Serviços não foram atualizados',
	'S_SERVICE_ADDED'=>			'Serviço incluído',
	'S_CANNOT_ADD_SERVICE'=>			'Serviço não foi incluído',
	'S_SERVICE_DELETED'=>			'Serviço removido',
	'S_CANNOT_DELETE_SERVICE'=>			'Serviço não foi removido',
	'S_STATUS_CALCULATION'=>			'Cálculo do status',
	'S_STATUS_CALCULATION_ALGORITHM'=>			'Algoritmo de cálculo do status',
	'S_NONE'=>			'Nenhum',
	'S_SOFT'=>			'Soft',
	'S_DO_NOT_CALCULATE'=>			'Não calcula',
	'S_ACCEPTABLE_SLA_IN_PERCENT'=>			'SLA  aceitável (em %)',
	'S_LINK_TO_TRIGGER_Q'=>			'Associar a trigger?',
	'S_SORT_ORDER_0_999'=>			'Posicionar em (0->999)',
	'S_TRIGGER'=>			'Trigger',
	'S_SERVER'=>			'Servidor',
	'S_DELETE'=>			'Remover',
	'S_CLONE'=>			'Clonar',
	'S_UPTIME'=>			'Uptime',
	'S_DOWNTIME'=>			'Downtime',
	'S_ONE_TIME_DOWNTIME'=>			'One-time downtime',
	'S_NO_TIMES_DEFINED'=>			'Nenhum definido',
	'S_SERVICE_TIMES'=>			'Horário de serviço',
	'S_NEW_SERVICE_TIME'=>			'Novo horário de serviço',
	'S_NOTE'=>			'Nota',
	'S_REMOVE'=>			'Remove',
	'S_DEPENDS_ON'=>			'Depende de',
	'S_SUNDAY'=>			'Domingo',
	'S_MONDAY'=>			'Segunda',
	'S_TUESDAY'=>			'Terça',
	'S_WEDNESDAY'=>			'Quarta',
	'S_THURSDAY'=>			'Quinta',
	'S_FRIDAY'=>			'Sexta',
	'S_SATURDAY'=>			'Sábado',
	'S_IT_SERVICES_BIG'=>			'SERVIÇOS IT',
	'S_SERVICE'=>			'Serviços',
	'S_SERVICES'=>			'Serviços',
	'S_REASON'=>			'Razão',
	'S_NO_TRIGGER'=>			'Sem trigger',
	'S_NO_TRIGGERS_DEFINED'=>			'Nenhuma trigger definida',
	'S_NO_TRIGGER_DEFINED'=>			'Nenhuma trigger definida',
	'S_CONFIGURATION_OF_TRIGGERS'=>			'Configuração das triggers',
	'S_CONFIGURATION_OF_TRIGGERS_BIG'=>			'CONFIGURAÇÃO DAS TRIGGERS',
	'S_TRIGGERS_DELETED'=>			'Triggers removidas',
	'S_CANNOT_DELETE_TRIGGERS'=>			'Triggers não foram removidas',
	'S_TRIGGER_DELETED'=>			'Trigger removida',
	'S_CANNOT_DELETE_TRIGGER'=>			'Trigger não foi removida',
	'S_TRIGGER_ADDED'=>			'Trigger adicionada',
	'S_CANNOT_ADD_TRIGGER'=>			'Tigger não foi adicionada',
	'S_SEVERITY'=>			'Risco',
	'S_EXPRESSION'=>			'Expressão',
	'S_DISABLED'=>			'Inativa',
	'S_ENABLED'=>			'Ativa',
	'S_ENABLE_SELECTED'=>			'Ativar selecionadas',
	'S_CHANGE'=>			'Alterar',
	'S_TRIGGER_UPDATED'=>			'Trigger atualizada',
	'S_CANNOT_UPDATE_TRIGGER'=>			'Trigger não foi atualizada',
	'S_URL'=>			'URL',
	'S_CREATE_TRIGGER'=>			'Criar Trigger',
	'S_INSERT'=>			'Inserir',
	'S_SECONDS'=>			'Segundos',
	'S_SEC_SMALL'=>			'seg',
	'S_LAST_OF'=>			'Última de',
	'S_SHOW_DISABLED_TRIGGERS'=>			'Mostrar triggers inativas',
	'S_HIDE_DISABLED_TRIGGERS'=>			'Esconder triggers inativas',
	'S_TRIGGER_COMMENTS'=>			'Comentários',
	'S_TRIGGER_COMMENTS_BIG'=>			'COMENTÁRIOS',
	'S_COMMENT_UPDATED'=>			'Comentário atualizado',
	'S_CANNOT_UPDATE_COMMENT'=>			'Comentário não foi alterado',
	'S_ADD'=>			'Adicionar',
	'S_STATUS_OF_TRIGGERS'=>			'Status das triggers',
	'S_STATUS_OF_TRIGGERS_BIG'=>			'STATUS DAS TRIGGERS',
	'S_SHOW_DETAILS'=>			'Mostrar detalhes',
	'S_SELECT'=>			'Selecionar',
	'S_TRIGGERS_BIG'=>			'TRIGGERS',
	'S_LAST_CHANGE'=>			'Última alteração',
	'S_COMMENTS'=>			'Comentários',
	'S_ACKNOWLEDGED'=>			'Visto',
	'S_ACK'=>			'Visto',
	'S_NEVER'=>			'Nunca',
	'S_ZABBIX_USER'=>			'Usuário ZABBIX',
	'S_ZABBIX_ADMIN'=>			'Administrador ZABBIX',
	'S_SUPER_ADMIN'=>			'Super Administrador ZABBIX',
	'S_USER_TYPE'=>			'Tipo de usuário',
	'S_USERS'=>			'Usuários',
	'S_USER_ADDED'=>			'Usuário incluído',
	'S_CANNOT_ADD_USER'=>			'Usuário não foi incluído',
	'S_CANNOT_ADD_USER_BOTH_PASSWORDS_MUST'=>			'Usuário não foi incluído. Senhas não conferem.',
	'S_USER_DELETED'=>			'Usuário removido',
	'S_CANNOT_DELETE_USER'=>			'Usuário não foi removido',
	'S_USER_UPDATED'=>			'Usuário atualizado',
	'S_CANNOT_UPDATE_USER'=>			'Usuário não foi atualizado',
	'S_CANNOT_UPDATE_USER_BOTH_PASSWORDS'=>			'Usuário não foi atualizado. Ambas as senhas devem ser iguais.',
	'S_GROUP_ADDED'=>			'Grupo incluído',
	'S_CANNOT_ADD_GROUP'=>			'Grupo não foi incluído',
	'S_GROUP_UPDATED'=>			'Grupo atualizado',
	'S_CANNOT_UPDATE_GROUP'=>			'Grupo não foi atualizado',
	'S_GROUP_DELETED'=>			'Grupo removido',
	'S_CANNOT_DELETE_GROUP'=>			'Grupo não foi removido',
	'S_CONFIGURATION_OF_USERS_AND_USER_GROUPS'=>			'CONFIGURAÇÃO DE USUÁRIOS E GRUPOS',
	'S_USER_GROUPS_BIG'=>			'GRUPOS DE USUÁRIOS',
	'S_USERS_BIG'=>			'USUÁRIOS',
	'S_USER_GROUPS'=>			'Grupos de usuários',
	'S_MEMBERS'=>			'Membros',
	'S_NO_USER_GROUPS_DEFINED'=>			'Nenhum grupo de usuário definido',
	'S_ALIAS'=>			'Alias',
	'S_NAME'=>			'Nome',
	'S_SURNAME'=>			'Sobrenome',
	'S_IS_ONLINE_Q'=>			'Online?',
	'S_NO_USERS_DEFINED'=>			'Não há usuários',
	'S_RIGHTS'=>			'Permissões',
	'S_NO_RIGHTS_DEFINED'=>			'Nenhuma permissão definida',
	'S_READ_ONLY'=>			'Somente leitura',
	'S_READ_WRITE'=>			'Leitura-escrita',
	'S_DENY'=>			'Nega',
	'S_HIDE'=>			'Esconde',
	'S_PASSWORD'=>			'Senha',
	'S_CHANGE_PASSWORD'=>			'Alterar senha',
	'S_PASSWORD_ONCE_AGAIN'=>			'Senha (novamente)',
	'S_URL_AFTER_LOGIN'=>			'URL (ao sair)',
	'S_SCREEN_REFRESH'=>			'Atualização da tela (em seg)',
	'S_CREATE_USER'=>			'Cria Usuário',
	'S_CREATE_GROUP'=>			'Criar Grupo',
	'S_DELETE_SELECTED_USERS_Q'=>			'Apagar usuários selecionados?',
	'S_NO_ACCESSIBLE_RESOURCES'=>			'Nenhum recurso acessível',
	'S_ACTION'=>			'Ação',
	'S_DETAILS'=>			'Detalhes',
	'S_UNKNOWN_ACTION'=>			'Ação desconhecida',
	'S_ADDED'=>			'Adicionado',
	'S_UPDATED'=>			'Atualizado',
	'S_MEDIA_TYPE'=>			'Tipo de mídia',
	'S_GRAPH_ELEMENT'=>			'Elemento gráfico',
	'S_UNKNOWN_RESOURCE'=>			'Recurso desconhecido',
	'S_USER_PROFILE_BIG'=>			'DADOS PERSONALIZADOS DO USUÁRIO',
	'S_USER_PROFILE'=>			'Dados personalizados do usuário',
	'S_LANGUAGE'=>			'Língua',
	'S_ENGLISH_GB'=>			'Inglês (GB)',
	'S_FRENCH_FR'=>			'Francês (FR)',
	'S_GERMAN_DE'=>			'Alemão (DE)',
	'S_ITALIAN_IT'=>			'Italiano (IT)',
	'S_LATVIAN_LV'=>			'Lituâno (LV)',
	'S_RUSSIAN_RU'=>			'Russo (RU)',
	'S_SPANISH_SP'=>			'Espanhol (SP)',
	'S_SWEDISH_SE'=>			'Sueco (SE)',
	'S_JAPANESE_JP'=>			'Japonês (JP)',
	'S_CHINESE_CN'=>			'Chinês (CN)',
	'S_DUTCH_NL'=>			'Holandês (NL)',
	'S_ZABBIX_BIG'=>			'ZABBIX',
	'S_HOST_PROFILES'=>			'Dados do host',
	'S_HOST_PROFILES_BIG'=>			'DADOS DO HOST',
	'S_EMPTY'=>			'Vazio',
	'S_STANDARD_ITEMS_BIG'=>			'ÍTENS PADRÃO',
	'S_NO_ITEMS'=>			'Não há ítens',
	'S_HELP'=>			'Help',
	'S_PROFILE'=>			'Configurações',
	'S_GET_SUPPORT'=>			'Suporte',
	'S_MONITORING'=>			'Monitoramento',
	'S_INVENTORY'=>			'Inventário',
	'S_QUEUE'=>			'Fila',
	'S_EVENTS'=>			'Eventos',
	'S_EVENTS_BIG'=>			'EVENTOS',
	'S_MAPS'=>			'Mapas',
	'S_REPORTS'=>			'Relatórios',
	'S_GENERAL'=>			'Geral',
	'S_AUDIT'=>			'Auditoria',
	'S_LOGIN'=>			'Login',
	'S_LOGOUT'=>			'Logout',
	'S_LATEST_DATA'=>			'Dados recentes',
	'S_INCORRECT_DESCRIPTION'=>			'Descrição incorreta',
	'S_CANT_FORMAT_TREE'=>			'Não consigo formatar árvore',
	'S_DO_NOT_ADD'=>                        'Do not add',
	'S_LEAVE_EMPTY'=>                       'Leave empty',
	'S_FILL_WITH_DEFAULT_VALUE'=>                   'Fill with default value',
	'S_CREATE'=>                            'Criar',
	'S_NEW_SMALL'=>                         'Novo',
	'S_LOCALES'=>                           'Idiomas',
	'S_LOCALE_SMALL'=>                      'idiomas',
	'S_DOWNLOAD'=>                          'Download',
	'S_AUTHENTICATION'=>                    'Autenticação',
	'S_AUTHENTICATION_TO_ZABBIX'=>          'Autenticaçãon  para ZABBIX',
	'S_BASE_DN'=>                                           'Base DN',
	'S_BIND_DN'=>                                           'Bind DN',
	'S_BIND_PASSWORD'=>                                     'Bind Password',
	'S_SEARCH_ATTRIBUTE'=>                          'Atributo de Pesquisa',
	'S_TEST'=>                                                      'Teste',
	'S_WAS_NOT'=>                                           'nao foi',
	'S_SUCCESSFUL_SMALL'=>                          'sucedido',
	'S_MUST_BE_VALID_SMALL'=>                       'deve ser valido',
	);
?>
