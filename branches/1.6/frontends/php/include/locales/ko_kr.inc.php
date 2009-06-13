<?php
/* 
** ZABBIX
** Copyright (C) 2000-2005 SIA Zabbix
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

	'S_DATE_FORMAT_YMDHMS'=>		'd M H:i:s',
	'S_HTML_CHARSET'=>			'UTF-8',

	'S_ACTIVATE_SELECTED'=>			'선택 항목 활성화',
	'S_DISABLE_SELECTED'=>			'선택 항목 비활성화',
	'S_DELETE_SELECTED'=>			'선택 항목 삭제',
	'S_COPY_SELECTED_TO'=>			'선택 항목 복사...',
	
//	dicoveryconf.php
	'S_HOST_IP'=>				'호스트 IP 주소',
	'S_SERVICE_TYPE'=>			'서비스 종류',
	'S_SERVICE_PORT'=>			'서비스 포트',
	'S_DISCOVERY_STATUS'=>			'디스커버리 상태',
	'S_RECEIVED_VALUE'=>			'수신 값',

	'S_UPTIME_DOWNTIME'=>			'업타임/다운타임',

	'S_DISCOVERY_RULE'=>			'디스커버리 규칙',
	'S_DISCOVERY'=>				'디스커버리',
	'S_DISCOVERY_BIG'=>			'디스커버리',
	'S_CONFIGURATION_OF_DISCOVERY'=>	'디스커버리 설정',
	'S_CONFIGURATION_OF_DISCOVERY_BIG'=>	'디스커버리 설정',
	'S_NO_DISCOVERY_RULES_DEFINED'=>	'디스커버리 규칙이 정의되어 있지 않습니다',
	'S_IP_RANGE'=>				'IP 주소 범위',
	'S_CHECKS'=>				'검사 항목',
	'S_ENABLE_SELECTED_RULES_Q'=>		'선택한 규칙을 활성화하시겠습니까?',
	'S_DISABLE_SELECTED_RULES_Q'=>		'선택한 규칙을 비활성화하시겠습니까?',
	'S_DELETE_SELECTED_RULES_Q'=>		'선택한 규칙을 삭제하시겠습니까?',
	'S_CREATE_RULE'=>			'규칙 작성',
	'S_DELETE_RULE_Q'=>			'규칙을 삭제하시겠습니까?',

	'S_EVENT_SOURCE'=>			'이벤트 소스',

	'S_NEW_CHECK'=>				'새 검사 항목',
	'S_SSH'=>				'SSH',
	'S_LDAP'=>				'LDAP',
	'S_SMTP'=>				'SMTP',
	'S_FTP'=>				'FTP',
	'S_HTTP'=>				'HTTP',
	'S_POP'=>				'POP',
	'S_NNTP'=>				'NNTP',
	'S_IMAP'=>				'IMAP',
	'S_TCP'=>				'TCP',
	'S_PORTS_SMALL'=>			'포트',

	'S_DISCOVERY_RULES_DELETED'=>		'디스커버리 규칙을 삭제하였습니다',
	'S_DISCOVERY_RULE_DELETED'=>		'디스커버리 규칙을 삭제하였습니다',
	'S_CANNOT_DELETE_DISCOVERY_RULE'=>	'디스커버리 규칙을 삭제할 수 없습니다',
	'S_DISCOVERY_RULES_UPDATED'=>		'디스커버리 규칙을 갱신하였습니다',
	'S_DISCOVERY_RULE_UPDATED'=>		'디스커버리 규칙을 갱신하였습니다',
	'S_CANNOT_UPDATE_DISCOVERY_RULE'=>	'디스커버리 규칙을 갱신할 수 없습니다',
	'S_DISCOVERY_RULE_ADDED'=>		'디스커버리 규칙을 추가하였습니다',
	'S_CANNOT_ADD_DISCOVERY_RULE'=>		'디스커버리 규칙을 추가할 수 없습니다',
	'S_STATUS_OF_DISCOVERY_BIG'=>		'디스커버리 상태',
	'S_STATUS_OF_DISCOVERY'=>		'디스커버리 상태',

//	httpdetails.php
	'S_DETAILS_OF_SCENARIO'=>		'시나리오 이름',
	'S_DETAILS_OF_SCENARIO_BIG'=>		'시나리오 이름',
	'S_SPEED'=>				'속도',
	'S_RESPONSE_CODE'=>			'응답 코드',
	'S_TOTAL_BIG'=>				'합계',
	'S_RESPONSE_TIME'=>			'응답 시간',
	'S_IN_PROGRESS'=>			'진행 중',
	'S_OF_SMALL'=>				'of',
	'S_IN_CHECK'=>				'검사 중',
	'S_IDLE_TILL'=>				'유휴 한계',
	'S_FAILED_ON'=>				'Failed on',

//	httpmon.php
	'S_STATUS_OF_WEB_MONITORING'=>		'웹 감시 상태',
	'S_STATUS_OF_WEB_MONITORING_BIG'=>	'웹 감시 상태',
	'S_STATE'=>				'상태',

//	httpconf.php
	'S_STATUS_CODES'=>			'상태 코드',
	'S_WEB'=>				'웹',
	'S_CONFIGURATION_OF_WEB_MONITORING'=>	'웹 감시 설정',
	'S_CONFIGURATION_OF_WEB_MONITORING_BIG'=>'웹 감시 설정',
	'S_SCENARIO'=>				'시나리오',
	'S_SCENARIOS'=>				'시나리오',
	'S_SCENARIOS_BIG'=>			'시나리오',
	'S_CREATE_SCENARIO'=>			'시나리오 작성',
	'S_HIDE_DISABLED_SCENARIOS'=>		'비활성 시나리오 감추기',
	'S_SHOW_DISABLED_SCENARIOS'=>		'비활성 시나리오 보이기',
	'S_NUMBER_OF_STEPS'=>			'스텝 수',
	'S_SCENARIO_DELETED'=>			'시나리오를 삭제하였습니다',
	'S_SCENARIO_ACTIVATED'=>		'시나리오를 활성화하였습니다',
	'S_SCENARIO_DISABLED'=>			'시나리오를 비활성화하였습니다',
	'S_ACTIVATE_SELECTED_SCENARIOS_Q'=>	'선택한 시나리오를 활성화하시겠습니까?',
	'S_DISABLE_SELECTED_SCENARIOS_Q'=>	'선택한 시나리오를 비활성화하시겠습니까?',
	'S_DELETE_SELECTED_SCENARIOS_Q'=>	'선택한 시나리오를 삭제하시겠습니까?',
	'S_DELETE_SCENARIO_Q'=>			'시나리오를 삭제하시겠습니까?',
	'S_CLEAN_HISTORY_SELECTED_SCENARIOS'=>	'선택한 시나리오 이력 삭제',
	'S_SCENARIO_UPDATED'=>			'시나리오를 갱신하였습니다',
	'S_CANNOT_UPDATE_SCENARIO'=>		'시나리오를 갱신할 수 없습니다',
	'S_SCENARIO_ADDED'=>			'시나리오를 추가하였습니다',
	'S_CANNOT_ADD_SCENARIO'=>		'시나리오를 추가할 수 없습니다',
	'S_SCENARIO_DELETED'=>			'시나리오를 삭제하였습니다',
	'S_CANNOT_DELETE_SCENARIO'=>		'시나리오를 삭제할 수 없습니다',
	'S_AGENT'=>				'에이전트',
	'S_VARIABLES'=>				'변수',
	'S_STEP'=>				'스텝',
	'S_STEPS'=>				'스텝',
	'S_TIMEOUT'=>				'타임아웃',
	'S_POST'=>				'POST',
	'S_REQUIRED'=>				'요구 문자열',
	'S_STEP_OF_SCENARIO'=>			'시나리오 스텝',
	
//	exp_imp.php
	'S_ELEMENT'=>				'항목',
	'S_ELEMENTS'=>				'항목',
	'S_ONLY_HOST_INFO'=>			'호스트 정보만',
	'S_EXPORT_IMPORT'=>			'내보내기/가져오기',
	'S_IMPORT_FILE'=>			'파일 가져오기',
	'S_IMPORT'=>				'가져오기',
	'S_IMPORT_BIG'=>			'가져오기',
	'S_EXPORT'=>				'내보내기',
	'S_EXPORT_BIG'=>			'내보내기',
	'S_PREVIEW'=>				'미리보기',
	'S_BACK'=>				'뒤로',
	'S_NO_DATA_FOR_EXPORT'=>		'내보낼 데이터가 없습니다',
	'S_RULES'=>				'규칙',
	'S_SKIP'=>				'건너뜀',
	'S_EXISTING'=>				'존재하는 경우',
	'S_MISSING'=>				'존재하지 않는 경우',
	'S_REFRESH'=>				'갱신',

//	admin.php
	'S_PREVIOUS'=>				'이전',
	'S_NEXT'=>				'다음',
	'S_RETRY'=>				'재실행',
	'S_FINISH'=>				'종료',
	'S_FAIL'=>				'실패',
	'S_UPDATE_BIG'=>			'갱신',
	'S_INSTALLATION'=>			'설치',
	'S_NEW_INSTALLATION'=>			'새로 설치',
	'S_NEW_INSTALLATION_BIG'=>		'새로 설치',
	'S_INSTALLATION_UPDATE'=>		'설치/갱신',
	
//	node.php
	'S_TIME_ZONE'=>				'시간대',
	'S_DO_NOT_KEEP_HISTORY_OLDER_THAN'=>	'이력 보존 기간(일)',
	'S_DO_NOT_KEEP_TRENDS_OLDER_THAN'=>	'트렌드 보존 기간(일)',
	'S_MASTER_NODE'=>			'마스터 노드',
	'S_REMOTE'=>				'리모트',
	'S_MASTER'=>				'마스터',
	'S_NODE_UPDATED'=>			'노드를 갱신하였습니다',
	'S_CANNOT_UPDATE_NODE'=>		'노드를 갱신할 수 없습니다',
	'S_NODE_ADDED'=>			'노드를 추가하였습니다',
	'S_CANNOT_ADD_NODE'=>			'노드를 추가할 수 없습니다',
	'S_NODE_DELETED'=>			'노드를 삭제하였습니다',
	'S_CANNOT_DELETE_NODE'=>		'노드를 삭제할 수 없습니다',
	'S_CURRENT_NODE'=>			'현재 노드',
	'S_CURRENT_NODE_ONLY'=>			'현재 노드만',
	'S_WITH_SUBNODES'=>			'하위 노드도 포함',
	
//	acknow.php
	'S_ACKNOWLEDGES'=>			'인지',
	'S_ACKNOWLEDGE'=>			'인지',
	'S_RETURN'=>				'뒤로',
	'S_ACKNOWLEDGE_ALARM_BY'=>		'인지 사용자:',
	'S_ADD_COMMENT_BY'=>			'주석 추가',
	'S_COMMENT_ADDED'=>			'주석을 추가하였습니다',
	'S_CANNOT_ADD_COMMENT'=>		'주석을 추가할 수 없습니다',
	'S_ALARM_ACKNOWLEDGES_BIG'=>		'알람 확인',
	'S_ACKNOWLEDGE_ADDED'=>			'인지를 추가하였습니다',

//	actionconf.php
	'S_CONFIGURATION_OF_ACTIONS'=>		'액션 설정',
	'S_CONFIGURATION_OF_ACTIONS_BIG'=>	'액션 설정',
	'S_OPERATION_TYPE'=>			'오퍼레이션 종류',
	'S_SEND_MESSAGE'=>			'메시지 송신',
	'S_REMOTE_COMMAND'=>			'원격 명령',
	'S_REMOTE_COMMANDS'=>			'원격 명령',
	'S_FILTER'=>				'필터',
	'S_TRIGGER_SEVERITY'=>			'트리거 심각도',
	'S_TRIGGER_VALUE'=>			'트리거 값',
	'S_TIME_PERIOD'=>			'기간',
	'S_TRIGGER_DESCRIPTION'=>		'트리거 이름',
	'S_CONDITIONS'=>			'조건',
	'S_CONDITION'=>				'조건',
	'S_NEW_CONDITION'=>			'새 조건',
	'S_OPERATIONS'=>			'오퍼레이션',
	'S_EDIT_OPERATION'=>			'오퍼레이션 편집',
	'S_NO_CONDITIONS_DEFINED'=>		'조건이 정의되어 있지 않습니다',
	'S_ACTIONS_DELETED'=>			'액션을 삭제하였습니다',
	'S_CANNOT_DELETE_ACTIONS'=>		'액션을 삭제할 수 없습니다',
	'S_NO_OPERATIONS_DEFINED'=>		'오퍼레이션이 정의되어 있지 않습니다',
	'S_NEW'=>				'새로 만들기',
	'S_ADD_HOST'=>				'호스트 추가',
	'S_REMOVE_HOST'=>			'호스트 삭제',
	'S_LINK_TO_TEMPLATE'=>			'템플릿과의 링크 작성',
	'S_UNLINK_FROM_TEMPLATE'=>		'템플릿과의 링크 삭제',

	'S_INCORRECT_TRIGGER'=>			'부적설한 트리거',
	'S_INCORRECT_HOST'=>			'부적절한 호스트',
	'S_INCORRECT_PERIOD'=>			'부적절한 기간',
	'S_INCORRECT_IP'=>			'부적절한 IP 주소',
	'S_INCORRECT_DISCOVERY_CHECK'=>		'부적절한 디스커버리 검사',
	'S_INCORRECT_PORT'=>			'부적절한 포트',
	'S_INCORRECT_DISCOVERY_STATUS'=>	'부적절한 디스커버리 상태',
	'S_INCORRECT_CONDITION_TYPE'=>		'부적절한 조건',

	'S_INCORRECT_OPERATION_TYPE'=>		'부적절한 오퍼레이션',
	'S_INCORRECT_USER'=>			'부적절한 사용자',

//	actions.php
	'S_ACTIONS'=>				'액션',
	'S_ACTIONS_BIG'=>			'액션',
	'S_ACTION_ADDED'=>			'액션을 추가하였습니다',
	'S_CANNOT_ADD_ACTION'=>			'액션을 추가할 수 없습니다',
	'S_ACTION_UPDATED'=>			'액션을 갱신하였습니다',
	'S_CANNOT_UPDATE_ACTION'=>		'액션을 갱신할 수 없습니다',
	'S_ACTION_DELETED'=>			'액션을 삭제하였습니다',
	'S_CANNOT_DELETE_ACTION'=>		'액션을 삭제할 수 없습니다',
	'S_SEND_MESSAGE_TO'=>			'메시지 송신처',
	'S_RUN_REMOTE_COMMANDS'=>		'원격 명령 실행',
	'S_DELAY'=>				'갱신 간격',
	'S_SUBJECT'=>				'제목',
	'S_ON'=>				'ON',
	'S_OFF'=>				'OFF',
	'S_NO_ACTIONS_DEFINED'=>		'액션이 정의되어 있지 않습니다',
	'S_SINGLE_USER'=>			'단일 사용자',
	'S_USER_GROUP'=>			'사용자 그룹',
	'S_GROUP'=>				'그룹',
	'S_USER'=>				'사용자',
	'S_MESSAGE'=>				'메시지',
	'S_NOT_CLASSIFIED'=>			'미분류',
	'S_INFORMATION'=>			'정보',
	'S_WARNING'=>				'경고',
	'S_AVERAGE'=>				'가벼운 장애',
	'S_HIGH'=>				'중증 장애',
	'S_DISASTER'=>				'심각한 장애',
	'S_AND_OR_BIG'=>			'AND / OR',
	'S_AND_BIG'=>				'AND',
	'S_AND'=>				'and',
	'S_AND_SYMB'=>                          '&',
	'S_OR_BIG'=>				'OR',
	'S_OR'=>				'or',
	'S_TYPE_OF_CALCULATION'=>		'계산 종류',
	'S_CREATE_ACTION'=>			'액션 작성',
	'S_ENABLE_SELECTED_ACTIONS_Q'=>		'선택한 액션을 활성화하시겠습니까?',
	'S_DISABLE_SELECTED_ACTIONS_Q'=>	'선택한 액션을 비활성화하시겠습니까?',
	'S_DELETE_SELECTED_ACTIONS_Q'=>		'선택한 액션을 삭제하시겠습니까?',
	'S_DELETE_SELECTED_ACTION_Q'=>		'선택한 액션을 삭제하시겠습니까?',
	'S_LIKE_SMALL'=>			'포함',
	'S_NOT_LIKE_SMALL'=>			'포함 안함',
	'S_IN_SMALL'=>				'범위 내',
	'S_NOT_IN_SMALL'=>			'범위 밖',
	'S_RETRIES_LEFT'=>			'남은 재시도 횟수',

//	alarms.php
	'S_SHOW_ALL'=>				'모두 보이기',
	'S_TIME'=>				'시각',
	'S_STATUS'=>				'상태',
	'S_DURATION'=>				'기간',
	'S_UNKNOWN_BIG'=>			'알 수 없음',

//	actions.php
	'S_TYPE'=>				'종류',
	'S_RECIPIENTS'=>			'수신처',
	'S_ERROR'=>				'에러',
	'S_SENT'=>				'송신 완료',
	'S_NOT_SENT'=>				'미송신',
	'S_NO_ACTIONS_FOUND'=>			'액션을 찾을 수 없습니다',

//	charts.php
	'S_CUSTOM_GRAPHS'=>			'커스텀 그래프',
	'S_GRAPHS_BIG'=>			'그래프',
	'S_SELECT_GRAPH_TO_DISPLAY'=>		'표시할 그래프를 선택하십시오',
	'S_PERIOD'=>				'기간',
	'S_MOVE'=>				'이동',
	'S_NAVIGATE'=>				'이동/조정',
	'S_INCREASE'=>				'증가',
	'S_DECREASE'=>				'감소',
	'S_NAVIGATE'=>				'이동/조정',
	'S_RIGHT_DIR'=>				'오른쪽',
	'S_LEFT_DIR'=>				'왼쪽',
	'S_SELECT_GRAPH_DOT_DOT_DOT'=>		'그래프 선택...',

// Colors

// Lines
        'S_LINE'=>                              '선',
        'S_FILLED_REGION'=>                     '면',
        'S_BOLD_LINE'=>                         '굵은 선',
        'S_DOT'=>                               '점선',
        'S_DASHED_LINE'=>                       '파선',

//	config.php
	'S_CANNNOT_UPDATE_VALUE_MAP'=>		'값 매핑을 갱신할 수 없습니다',
	'S_VALUE_MAP_ADDED'=>			'값 매핑을 추가하였습니다',
	'S_CANNNOT_ADD_VALUE_MAP'=>		'값 매핑을 추가할 수 없습니다',
	'S_VALUE_MAP_DELETED'=>			'값 매핑을 삭제하였습니다',
	'S_CANNNOT_DELETE_VALUE_MAP'=>		'값 매핑을 삭제할 수 없습니다',
	'S_VALUE_MAP_UPDATED'=>			'값 매핑을 갱신하였습니다',
	'S_VALUE_MAPPING_BIG'=>			'값 매핑',
	'S_VALUE_MAPPING'=>			'값 매핑',
	'S_VALUE_MAP'=>				'값 매핑',
	'S_MAPPING'=>				'매핑',
	'S_NEW_MAPPING'=>			'새 매핑',
	'S_NO_MAPPING_DEFINED'=>		'매핑이 정의되어 있지 않습니다',
	'S_CREATE_VALUE_MAP'=>			'값 매핑 작성',
	'S_CONFIGURATION_OF_ZABBIX'=>		'ZABBIX 설정',
	'S_CONFIGURATION_OF_ZABBIX_BIG'=>	'ZABBIX 설정',
	'S_CONFIGURATION_UPDATED'=>		'설정을 갱신하였습니다',
	'S_CONFIGURATION_WAS_NOT_UPDATED'=>	'설정을 갱신할 수 없습니다',
	'S_ADDED_NEW_MEDIA_TYPE'=>		'연락 방법을 추가하였습니다',
	'S_NEW_MEDIA_TYPE_WAS_NOT_ADDED'=>	'연락 방법을 추가할 수 없습니다',
	'S_MEDIA_TYPE_UPDATED'=>		'연락 방법을 갱신하였습니다',
	'S_MEDIA_TYPE_WAS_NOT_UPDATED'=>	'연락 방법을 갱신할 수 없습니다',
	'S_MEDIA_TYPE_DELETED'=>		'연락 방법을 삭제하였습니다',
	'S_MEDIA_TYPE_WAS_NOT_DELETED'=>	'연락 방법을 삭제할 수 없습니다',
	'S_CONFIGURATION'=>			'설정',
	'S_ADMINISTRATION'=>			'관리',
	'S_DO_NOT_KEEP_ACTIONS_OLDER_THAN'=>	'액션 보존 기간(일)',
	'S_DO_NOT_KEEP_EVENTS_OLDER_THAN'=>	'이벤트 보존 기간(일)',
	'S_NO_MEDIA_TYPES_DEFINED'=>		'연락 방법이 정의되어 있지 않습니다',
	'S_SMTP_SERVER'=>			'SMTP 서버',
	'S_SMTP_HELO'=>				'SMTP helo',
	'S_SMTP_EMAIL'=>			'발송자 전자우편 주소',
	'S_SCRIPT_NAME'=>			'스크립트 이름',
	'S_DELETE_SELECTED_MEDIA'=>		'선택한 연락 방법을 삭제하시겠습니까?',
	'S_DELETE_SELECTED_IMAGE'=>		'선택한 이미지를 삭제하시겠습니까?',
	'S_HOUSEKEEPER'=>			'데이터 보존 기간',
	'S_MEDIA_TYPES'=>			'연락 방법',
	'S_ESCALATION_RULES'=>			'에스컬레이션 규칙',
	'S_DEFAULT'=>				'Default',
	'S_IMAGES'=>				'이미지',
	'S_IMAGE'=>				'이미지',
	'S_IMAGES_BIG'=>			'이미지',
	'S_ICON'=>				'아이콘',
	'S_NO_IMAGES_DEFINED'=>			'이미지가 정의되어 있지 않습니다',
	'S_BACKGROUND'=>			'배경',
	'S_UPLOAD'=>				'업로드',
	'S_IMAGE_ADDED'=>			'이미지를 추가하였습니다',
	'S_CANNOT_ADD_IMAGE'=>			'이미지를 추가할 수 없습니다',
	'S_IMAGE_DELETED'=>			'이미지를 삭제하였습니다',
	'S_CANNOT_DELETE_IMAGE'=>		'이미지를 삭제할 수 없습니다',
	'S_IMAGE_UPDATED'=>			'이미지를 갱신하였습니다',
	'S_CANNOT_UPDATE_IMAGE'=>		'이미지를 갱신할 수 없습니다',
	'S_OTHER'=>				'기타',
	'S_OTHER_PARAMETERS'=>			'기타 파라미터',
	'S_REFRESH_UNSUPPORTED_ITEMS'=>		'취득불가 아이템 갱신 간격(초)',
	'S_CREATE_MEDIA_TYPE'=>			'연락 방법 작성',
	'S_CREATE_IMAGE'=>			'이미지 작성',
	'S_CREATE_RULE'=>			'규칙 작성',
	'S_WORKING_TIME'=>			'Working time',
	'S_USER_GROUP_FOR_DATABASE_DOWN_MESSAGE'=>'데이터베이스 정지 메시지 송신 대상 사용자 그룹',
	'S_INCORRECT_GROUP'=>			'부적절한 그룹',
	'S_NOTHING_TO_DO'=>			'실행할 것이 없습니다',
	'S_ICORRECT_WORK_PERIOD'=>		'부적절한 Working time',
	
//	nodes.php
	'S_NODE'=>				'노드',
	'S_NODES'=>				'노드',
	'S_NODES_BIG'=>				'노드',
	'S_NEW_NODE'=>				'새 노드',
	'S_NO_NODES_DEFINED'=>			'노드가 정의되어 있지 않습니다',

//	Latest values
	'S_NO_PERMISSIONS'=>			'권한이 없습니다!',
	'S_LATEST_DATA_BIG'=>			'최근 데이터',
	'S_ALL_SMALL'=>				'전체',
	'S_MINUS_OTHER_MINUS'=>			'- 기타 -',
	'S_GRAPH'=>				'그래프',

//	Footer
	'S_CONNECTED_AS'=>			'다음 사용자로 로그인 중',
	'S_SIA_ZABBIX'=>			'SIA Zabbix',

//	graph.php
	'S_ITEM_ADDED'=>			'아이템을 추가하였습니다',
	'S_ITEM_UPDATED'=>			'아이템을 갱신하였습니다',
	'S_ITEMS_UPDATED'=>			'아이템을 갱신하였습니다',
	'S_PARAMETER'=>				'파라미터',
	'S_COLOR'=>				'색',
	'S_UP'=>				'UP',
	'S_DOWN'=>				'DOWN',
	'S_NEW_ITEM_FOR_THE_GRAPH'=>		'그래프에 추가할 아이템',
	'S_SORT_ORDER_1_100'=>			'정렬 순서(0->100)',
	'S_YAXIS_SIDE'=>			'Y축',
	'S_LEFT'=>				'왼쪽',
	'S_FUNCTION'=>				'기능',
	'S_MIN_SMALL'=>				'최소',
	'S_AVG_SMALL'=>				'평균',
	'S_MAX_SMALL'=>				'최대',
	'S_DRAW_STYLE'=>			'종류',
	'S_SIMPLE'=>				'단순',
	'S_GRAPH_TYPE'=>			'그래프 종류',
	'S_STACKED'=>				'누적 그래프',
	'S_NORMAL'=>				'꺾은선 그래프',
	'S_AGGREGATED'=>			'누계',
	'S_AGGREGATED_PERIODS_COUNT'=>			'누계 주기 횟수',

//	graphs.php
	'S_CONFIGURATION_OF_GRAPHS'=>		'그래프 설정',
	'S_CONFIGURATION_OF_GRAPHS_BIG'=>	'그래프 설정',
	'S_GRAPH_ADDED'=>			'그래프를 추가하였습니다',
	'S_GRAPH_UPDATED'=>			'그래프를 갱신하였습니다',
	'S_CANNOT_UPDATE_GRAPH'=>		'그래프를 갱신할 수 없습니다',
	'S_GRAPH_DELETED'=>			'그래프를 삭제하였습니다',
	'S_CANNOT_DELETE_GRAPH'=>		'그래프를 삭제할 수 없습니다',
	'S_CANNOT_ADD_GRAPH'=>			'그래프를 추가할 수 없습니다',
	'S_ID'=>				'ID',
	'S_NO_GRAPHS_DEFINED'=>			'그래프가 정의되어 있지 않습니다',
	'S_NO_GRAPH_DEFINED'=>			'그래프가 정의되어 있지 않습니다',
	'S_DELETE_GRAPH_Q'=>			'그래프를 삭제하시겠습니까?',
	'S_YAXIS_TYPE'=>			'Y축 종류',
	'S_YAXIS_MIN_VALUE'=>			'Y축 최솟값',
	'S_YAXIS_MAX_VALUE'=>			'Y축 최댓값',
	'S_CALCULATED'=>			'자동 계산',
	'S_FIXED'=>				'고정',
	'S_CREATE_GRAPH'=>			'그래프 작성',
	'S_SHOW_WORKING_TIME'=>			'Woring time 표시',
	'S_SHOW_TRIGGERS'=>			'트리거 표시',
	'S_GRAPH_ITEM'=>			'그래프 아이템',
	'S_REQUIRED_ITEMS_FOR_GRAPH'=>		'그래프에 필요한 아이템',

//	history.php
	'S_LAST_HOUR_GRAPH'=>			'최근 1시간의 그래프',
	'S_LAST_WEEK_GRAPH'=>			'최근 1주일의 그래프',
	'S_LAST_MONTH_GRAPH'=>			'최근 1개월의 그래프',
	'S_500_LATEST_VALUES'=>			'최근 500개의 값',
	'S_GRAPH_OF_SPECIFIED_PERIOD'=>		'특정 기간의 그래프',
	'S_VALUES_OF_SPECIFIED_PERIOD'=>	'특정 기간의 값',
	'S_TIMESTAMP'=>				'타임 스탬프',
	'S_LOCAL'=>				'로컬',
	'S_SOURCE'=>				'소스',
	'S_SHOW_UNKNOWN'=>			'알 수 없는 항목 보이기',

	'S_SHOW_SELECTED'=>			'선택 항목 보이기',
	'S_HIDE_SELECTED'=>			'선택 항목 감추기',
	'S_MARK_SELECTED'=>			'선택 항목 색 지정',
	'S_MARK_OTHERS'=>			'선택 이외 항목 색 지정',

	'S_AS_RED'=>				'빨강',
	'S_AS_GREEN'=>				'녹색',
	'S_AS_BLUE'=>				'파랑',

//	hosts.php
	'S_APPLICATION'=>			'애플리케이션',
	'S_APPLICATIONS'=>			'애플리케이션',
	'S_APPLICATIONS_BIG'=>			'애플리케이션',
	'S_CREATE_APPLICATION'=>		'애플리케이션 작성',
	'S_DELETE_SELECTED_APPLICATIONS_Q'=>	'선택한 애플리케이션을 삭제하시겠습니까?',
	'S_DISABLE_ITEMS_FROM_SELECTED_APPLICATIONS_Q'=>'선택한 애플리케이션의 아이템을 비활성화하시겠습니까?',
	'S_ACTIVATE_ITEMS'=>			'아이템 활성화',
	'S_DISABLE_ITEMS'=>			'아이템 비활성화',
	'S_ACTIVATE_ITEMS_FROM_SELECTED_APPLICATIONS_Q'=>'선택한 애플리케이션의 아이템을 활성화하시겠습니까?',
	'S_APPLICATION_UPDATED'=>		'애플리케이션을 갱신하였습니다',
	'S_CANNOT_UPDATE_APPLICATION'=>		'애플리케이션을 갱신할 수 없습니다',
	'S_APPLICATION_ADDED'=>			'애플리케이션을 추가하였습니다',
	'S_CANNOT_ADD_APPLICATION'=>		'애플리케이션을 추가할 수 없습니다',
	'S_APPLICATION_DELETED'=>		'애플리케이션을 삭제하였습니다',
	'S_CANNOT_DELETE_APPLICATION'=>		'애플리케이션을 삭제할 수 없습니다',
	'S_NO_APPLICATIONS_DEFINED'=>		'애플리케이션이 정의되어 있지 않습니다',

	'S_HOSTS'=>				'호스트',
	'S_ITEMS'=>				'아이템',
	'S_ITEMS_BIG'=>				'아이템',
	'S_TRIGGERS'=>				'트리거',
	'S_GRAPHS'=>				'그래프',
	'S_HOST_ADDED'=>			'호스트를 추가하였습니다',
	'S_CANNOT_ADD_HOST'=>			'호스트를 추가할 수 없습니다',
	'S_HOST_UPDATED'=>			'호스트를 갱신하였습니다',
	'S_CANNOT_UPDATE_HOST'=>		'호스트를 갱신할 수 없습니다',
	'S_HOST_STATUS_UPDATED'=>		'호스트 상태를 갱신하였습니다',
	'S_CANNOT_UPDATE_HOST_STATUS'=>		'호스트 상태를 갱신할 수 없습니다',
	'S_HOST_DELETED'=>			'호스트를 삭제하였습니다',
	'S_CANNOT_DELETE_HOST'=>		'호스트를 삭제할 수 없습니다',
	'S_CONFIGURATION_OF_HOSTS_GROUPS_AND_TEMPLATES'=>'호스트/그룹/템플릿 설정',
	'S_HOST_GROUPS_BIG'=>			'호스트 그룹',
	'S_NO_HOST_GROUPS_DEFINED'=>		'호스트 그룹이 정의되어 있지 않습니다',
	'S_NO_HOSTS_DEFINED'=>			'호스트가 정의되어 있지 않습니다',
	'S_NO_TEMPLATES_DEFINED'=>		'템플릿이 정의되어 있지 않습니다',
	'S_HOSTS_BIG'=>				'호스트',
	'S_HOST'=>				'호스트',
	'S_CONNECT_TO'=>			'접속 방법',
	'S_DNS'=>				'DNS',
	'S_IP'=>				'IP 주소',
	'S_PORT'=>				'포트',
	'S_MONITORED'=>				'감시 중',
	'S_NOT_MONITORED'=>			'미감시',
	'S_TEMPLATE'=>				'템플릿',
	'S_DELETED'=>				'삭제',
	'S_UNKNOWN'=>				'알 수 없음',
	'S_GROUPS'=>				'그룹',
	'S_NO_GROUPS_DEFINED'=>			'그룹이 정의되어 있지 않습니다',
	'S_NEW_GROUP'=>				'새 그룹',
	'S_DNS_NAME'=>				'DNS 이름',
	'S_IP_ADDRESS'=>			'IP 주소',
	'S_LINK_WITH_TEMPLATE'=>		'템플릿 링크',
	'S_USE_PROFILE'=>			'프로파일 사용',
	'S_DELETE_SELECTED_HOST_Q'=>		'선택한 호스트를 삭제하시겠습니까?',
	'S_DELETE_SELECTED_GROUPS_Q'=>		'선택한 그룹을 삭제하시겠습니까?',
	'S_DELETE_SELECTED_WITH_LINKED_ELEMENTS'=>'선택 항목 및 연결된 구성요소 삭제',
	'S_GROUP_NAME'=>			'그룹 이름',
	'S_HOST_GROUP'=>			'호스트 그룹',
	'S_HOST_GROUPS'=>			'호스트 그룹',
	'S_UPDATE'=>				'갱신',
	'S_AVAILABILITY'=>			'상태',
	'S_AVAILABLE'=>				'감시 중',
	'S_NOT_AVAILABLE'=>			'미감시',
//	Host profiles
	'S_HOST_PROFILE'=>			'호스트 프로파일',
	'S_DEVICE_TYPE'=>			'장치 종류',
	'S_OS'=>				'OS',
	'S_SERIALNO'=>				'일련번호',
	'S_TAG'=>				'태그',
	'S_HARDWARE'=>				'하드웨어',
	'S_SOFTWARE'=>				'소프트웨어',
	'S_CONTACT'=>				'연락처',
	'S_LOCATION'=>				'장소',
	'S_NOTES'=>				'비고',
	'S_MACADDRESS'=>			'MAC 주소',
	'S_ADD_TO_GROUP'=>			'그룹에 추가',
	'S_DELETE_FROM_GROUP'=>			'그룹에서 삭제',
	'S_UPDATE_IN_GROUP'=>			'그룹 갱신',
	'S_DELETE_SELECTED_HOSTS_Q'=>		'선택한 호스트를 삭제하시겠습니까?',
	'S_DISABLE_SELECTED_HOSTS_Q'=>		'선택한 호스트를 비활성화하시겠습니까?',
	'S_ACTIVATE_SELECTED_HOSTS_Q'=>		'선택한 호스트를 활성화하시겠습니까?',
	'S_CREATE_HOST'=>			'호스트 작성',
	'S_CREATE_TEMPLATE'=>			'템플릿 작성',
	'S_TEMPLATE_LINKAGE'=>			'템플릿 링크',
	'S_TEMPLATE_LINKAGE_BIG'=>		'템플릿 링크',
	'S_NO_LINKAGES'=>			'링크가 없습니다',
	'S_TEMPLATES'=>				'템플릿',
	'S_TEMPLATES_BIG'=>			'템플릿',
	'S_HOSTS'=>				'호스트',
	'S_UNLINK'=>				'링크 삭제',
	'S_UNLINK_AND_CLEAR'=>			'링크와 보존 데이터 삭제',

//	items.php
	'S_NO_ITEMS_DEFINED'=>			'아이템이 정의되어 있지 않습니다',
	'S_NO_ITEM_DEFINED'=>			'아이템이 정의되어 있지 않습니다',
	'S_HISTORY_CLEANED'=>			'이력이 삭제되었습니다',
	'S_CLEAN_HISTORY_SELECTED_ITEMS'=>	'선택 아이템 이력 삭제',
	'S_CLEAN_HISTORY'=>			'이력 삭제',
	'S_CANNOT_CLEAN_HISTORY'=>		'이력을 삭제할 수 없습니다',
	'S_CONFIGURATION_OF_ITEMS'=>		'아이템 설정',
	'S_CONFIGURATION_OF_ITEMS_BIG'=>	'아이템 설정',
	'S_CANNOT_UPDATE_ITEM'=>		'아이템을 갱신할 수 없습니다',
	'S_STATUS_UPDATED'=>			'상태를 갱신하였습니다',
	'S_CANNOT_UPDATE_STATUS'=>		'상태를 갱신할 수 없습니다',
	'S_CANNOT_ADD_ITEM'=>			'아이템을 추가할 수 없습니다',
	'S_ITEM_DELETED'=>			'아이템을 삭제하였습니다',
	'S_CANNOT_DELETE_ITEM'=>		'아이템을 삭제할 수 없습니다',
	'S_ITEMS_DELETED'=>			'아이템을 삭제하였습니다',
	'S_CANNOT_DELETE_ITEMS'=>		'아이템을 삭제할 수 없습니다',
	'S_ITEMS_ACTIVATED'=>			'아이템을 활성화하였습니다',
	'S_ITEMS_DISABLED'=>			'아이템을 비활성화하였습니다',
	'S_KEY'=>				'키',
	'S_DESCRIPTION'=>			'이름',
	'S_UPDATE_INTERVAL'=>			'갱신 간격',
	'S_HISTORY'=>				'이력',
	'S_TRENDS'=>				'트렌드',
	'S_ZABBIX_AGENT'=>			'ZABBIX 에이전트',
	'S_ZABBIX_AGENT_ACTIVE'=>		'ZABBIX 에이전트(활성)',
	'S_SNMPV1_AGENT'=>			'SNMPv1 에이전트',
	'S_ZABBIX_TRAPPER'=>			'ZABBIX trapper',
	'S_SIMPLE_CHECK'=>			'간단 검사',
	'S_SNMPV2_AGENT'=>			'SNMPv2 에이전트',
	'S_SNMPV3_AGENT'=>			'SNMPv3 에이전트',
	'S_ZABBIX_INTERNAL'=>			'ZABBIX 내장',
	'S_ZABBIX_AGGREGATE'=>			'ZABBIX 집합',
	'S_EXTERNAL_CHECK'=>			'외부 검사',
	'S_WEB_MONITORING'=>			'웹 감시',
	'S_ACTIVE'=>				'활성',
	'S_NOT_SUPPORTED'=>			'취득 불가',
	'S_ACTIVATE_SELECTED_ITEMS_Q'=>		'선택한 아이템을 활성화하시겠습니까?',
	'S_DISABLE_SELECTED_ITEMS_Q'=>		'선택한 아이템을 비활성화하시겠습니까?',
	'S_DELETE_SELECTED_ITEMS_Q'=>		'선택한 아이템을 삭제하시겠습니까?',
	'S_EMAIL'=>				'전자우편',
	'S_JABBER'=>				'Jabber',
	'S_JABBER_IDENTIFIER'=>			'JabberID',
	'S_SMS'=>				'SMS',
	'S_SCRIPT'=>				'스크립트',
	'S_GSM_MODEM'=>				'GSM 모뎀',
	'S_UNITS'=>				'단위',
	'S_UPDATE_INTERVAL_IN_SEC'=>		'갱신 간격(초)',
	'S_KEEP_HISTORY_IN_DAYS'=>		'이력 보존 기간(일)',
	'S_KEEP_TRENDS_IN_DAYS'=>		'경향 보존 기간(일)',
	'S_TYPE_OF_INFORMATION'=>		'데이터형',
	'S_STORE_VALUE'=>			'보존 시 계산',
	'S_SHOW_VALUE'=>			'값 매핑 사용',
	'S_NUMERIC_UNSIGNED'=>			'숫자(integer 64bit)',
	'S_NUMERIC_FLOAT'=>			'숫자(float)',
	'S_CHARACTER'=>				'문자',
	'S_LOG'=>				'로그',
	'S_TEXT'=>				'텍스트',
	'S_AS_IS'=>				'하지 않음',
	'S_DELTA_SPEED_PER_SECOND'=>		'델타(변경 값/초)',
	'S_DELTA_SIMPLE_CHANGE'=>		'델타',
	'S_ITEM'=>				'아이템',
	'S_SNMP_COMMUNITY'=>			'SNMP community',
	'S_SNMP_OID'=>				'SNMP OID',
	'S_SNMP_PORT'=>				'SNMP 포트',
	'S_ALLOWED_HOSTS'=>			'허가된 호스트',
	'S_SNMPV3_SECURITY_NAME'=>		'SNMPv3 security name',
	'S_SNMPV3_SECURITY_LEVEL'=>		'SNMPv3 security level',
	'S_SNMPV3_AUTH_PASSPHRASE'=>		'SNMPv3 auth passphrase',
	'S_SNMPV3_PRIV_PASSPHRASE'=>		'SNMPv3 priv passphrase',
	'S_CUSTOM_MULTIPLIER'=>			'승수',
	'S_DO_NOT_USE'=>			'사용 안함',
	'S_USE_MULTIPLIER'=>			'승수 사용',
	'S_LOG_TIME_FORMAT'=>			'로그 시간 형식',
	'S_CREATE_ITEM'=>			'아이템 작성',
	'S_X_ELEMENTS_COPY_TO_DOT_DOT_DOT'=>	'아이템 복사 대상...',
	'S_MODE'=>				'모드',
	'S_TARGET'=>				'대상',
	'S_TARGET_TYPE'=>			'대상 종류',
	'S_SKIP_EXISTING_ITEMS'=>		'기존 아이템을 건너뜀',
	'S_UPDATE_EXISTING_NON_LINKED_ITEMS'=>	'기존 미 연결 아이템을 갱신',
	'S_COPY'=>				'복사',
	'S_SHOW_ITEMS_WITH_DESCRIPTION_LIKE'=>	'이름에 다음 문자열을 포함한 아이템만 보이기',
	'S_SHOW_DISABLED_ITEMS'=>               '비활성 아이템 보이기',
	'S_HIDE_DISABLED_ITEMS'=>               '비활성 아이템 감추기',
	'S_HISTORY_CLEANING_CAN_TAKE_A_LONG_TIME_CONTINUE_Q' => '이력 삭제에 시간이 걸릴 수도 있습니다. 계속하시겠습니까?',
	'S_SELECTION_MODE'=>			'선택 모드',
	'S_ADVANCED'=>				'확장',
	'S_SIMPLE'=>				'표준',
	'S_MASS_UPDATE'=>			'일괄갱신',
	'S_ORIGINAL'=>				'변경 안함',
	'S_NEW_FLEXIBLE_INTERVAL'=>		'새로운 유동적인 갱신 간격',
	'S_FLEXIBLE_INTERVALS'=>		'유동적인 갱신 간격(초)',
        'S_NO_FLEXIBLE_INTERVALS'=>             '유동적인 갱신 간격이 정의되어 있지 않습니다',

//	events.php
	'S_LATEST_EVENTS'=>			'최근 이벤트',
	'S_HISTORY_OF_EVENTS_BIG'=>		'이벤트 이력',
	'S_NO_EVENTS_FOUND'=>			'이벤트를 찾을 수 없습니다',

//	latest.php
	'S_LAST_CHECK'=>			'최근 검사',
	'S_LAST_VALUE'=>			'최근 값',

//	sysmap.php
	'S_LINK'=>				'연결',
	'S_X'=>					'X',
	'S_Y'=>					'Y',
	'S_ICON_ON'=>				'아이콘(장애)',
	'S_ICON_OFF'=>				'아이콘(정상)',
	'S_ICON_UNKNOWN'=>			'아이콘(알 수 없음)',
	'S_ELEMENT_1'=>				'구성요소1',
	'S_ELEMENT_2'=>				'구성요소2',
	'S_LINK_STATUS_INDICATOR'=>		'연결 상태 표시기',
	'S_CONFIGURATION_OF_NETWORK_MAPS'=>	'네트워크 맵 설정',
	'S_CONFIGURATION_OF_NETWORK_MAPS_BIG'=>	'네트워크 맵 설정',
	'S_DISPLAYED_ELEMENTS'=>		'표시 구성요소',
	'S_CONNECTORS'=>			'연결',
	'S_ADD_ELEMENT'=>			'표시 구성요소 추가',
	'S_CREATE_CONNECTION'=>			'연결 작성',
	'S_COORDINATE_X'=>			'X좌표',
	'S_COORDINATE_Y'=>			'Y좌표',
	
	

//	sysmaps.php
	'S_MAPS_BIG'=>				'맵',
	'S_NO_MAPS_DEFINED'=>			'맵이 정의되어 있지 않습니다',
	'S_CREATE_MAP'=>			'맵 작성',
	'S_ICON_LABEL_LOCATION'=>		'아이콘 라벨 위치',
	'S_BOTTOM'=>				'아래쪽',
	'S_TOP'=>				'위쪽',

//	map.php
	'S_OK_BIG'=>				'정상',
	'S_ZABBIX_URL'=>			'http://www.zabbix.com',

//	maps.php
	'S_NETWORK_MAPS'=>			'네트워크 맵',
	'S_NETWORK_MAPS_BIG'=>			'네트워크 맵',
	'S_BACKGROUND_IMAGE'=>			'배경 이미지',
	'S_ICON_LABEL_TYPE'=>			'아이콘 라벨 종류',
	'S_LABEL'=>				'라벨',
	'S_LABEL_LOCATION'=>			'라벨 위치',
	'S_ELEMENT_NAME'=>			'구성요소 이름',
	'S_STATUS_ONLY'=>			'상태만',
	'S_NOTHING'=>				'없음',

//	media.php
	'S_CONFIGURATION_OF_MEDIA_TYPES_BIG'=>	'연락 방법 설정',
	'S_MEDIA'=>				'연락 방법',
	'S_SEND_TO'=>				'수신처',
	'S_WHEN_ACTIVE'=>			'연락 허용 시간대',
	'S_NO_MEDIA_DEFINED'=>			'연락 방법이 정의되어 있지 않습니다',
	'S_NEW_MEDIA'=>				'새 연락 방법',
	'S_USE_IF_SEVERITY'=>			'심각도 선택',
	'S_SAVE'=>				'저장',
	'S_CANCEL'=>				'취소',

//	Menu

//	overview.php
	'S_OVERVIEW'=>				'개요',
	'S_OVERVIEW_BIG'=>			'개요',
	'S_DATA'=>				'데이터',
	'S_SHOW_GRAPH_OF_ITEM'=>		'아이템의 그래프 표시',
	'S_SHOW_VALUES_OF_ITEM'=>		'아이템의 값 표시',
	'S_VALUES'=>				'값',
	'S_5_MIN'=>				'5분',
	'S_15_MIN'=>				'15분',

//	queue.php
	'S_QUEUE_BIG'=>				'큐',
	'S_QUEUE_OF_ITEMS_TO_BE_UPDATED_BIG'=>	'갱신 아이템 큐',
	'S_NEXT_CHECK'=>			'다음 검사',
	'S_THE_QUEUE_IS_EMPTY'=>		'큐가 비어 있습니다',
	'S_TOTAL'=>				'합계',
	'S_COUNT'=>				'회',
	'S_5_SECONDS'=>				'5초',
	'S_10_SECONDS'=>			'10초',
	'S_30_SECONDS'=>			'30초',
	'S_1_MINUTE'=>				'1분',
	'S_5_MINUTES'=>				'5분',
	'S_MORE_THAN_5_MINUTES'=>		'5분 이상',

//	report1.php
	'S_STATUS_OF_ZABBIX'=>			'ZABBIX 서버 상태',
	'S_STATUS_OF_ZABBIX_BIG'=>		'ZABBIX 서버 상태',
	'S_VALUE'=>				'값',
	'S_ZABBIX_SERVER_IS_RUNNING'=>		'ZABBIX 서버 가동 중',
	'S_VALUES_STORED'=>			'Values stored',
	'S_TRENDS_STORED'=>			'Trends stored',
	'S_NUMBER_OF_EVENTS'=>			'이벤트 수',
	'S_NUMBER_OF_ALERTS'=>			'경고 수',
	'S_NUMBER_OF_TRIGGERS'=>		'트리거 수(활성/비활성)[장애/알 수 없음/정상]',
	'S_NUMBER_OF_TRIGGERS_SHORT'=>		'트리거 수(활성/비활성)[장애/알 수 없음/정상]',
	'S_NUMBER_OF_ITEMS'=>			'아이템 수(감시/미감시/취득불가)[trapper]',
	'S_NUMBER_OF_ITEMS_SHORT'=>		'아이템 수(감시/미감시/취득불가)[trapper]',
	'S_NUMBER_OF_USERS'=>			'사용자 수 (온라인)',
	'S_NUMBER_OF_USERS_SHORT'=>		'사용자 (온라인)',
	'S_NUMBER_OF_HOSTS'=>			'호스트 수 (감시/미감시/템플릿/삭제됨)',
	'S_NUMBER_OF_HOSTS_SHORT'=>		'호스트 수 (감시/미감시/템플릿/삭제됨)',
	'S_YES'=>				'예',
	'S_NO'=>				'아니오',
	'S_RUNNING'=>				'가동 중',
	'S_NOT_RUNNING'=>			'정지 중',

//	report2.php
	'S_AVAILABILITY_REPORT'=>		'가동 보고서',
	'S_AVAILABILITY_REPORT_BIG'=>		'가동 보고서',
	'S_SHOW'=>				'표시',
	'S_TRUE'=>				'장애',
	'S_FALSE'=>				'정상',

//	report3.php
	'S_IT_SERVICES_AVAILABILITY_REPORT'=>	'IT 서비스 가동 보고서',
	'S_IT_SERVICES_AVAILABILITY_REPORT_BIG'=>	'IT 서비스 가동 보고서',
	'S_FROM'=>				'개시',
	'S_FROM_SMALL'=>			'개시',
	'S_TILL'=>				'종료',
	'S_OK'=>				'OK',
	'S_PROBLEMS'=>				'장애',
	'S_PERCENTAGE'=>			'퍼센트',
	'S_SLA'=>				'SLA',
	'S_DAY'=>				'일',
	'S_MONTH'=>				'월',
	'S_YEAR'=>				'년',
	'S_DAILY'=>				'일간',
	'S_WEEKLY'=>				'주간',
	'S_MONTHLY'=>				'월간',
	'S_YEARLY'=>				'연간',

//      report4.php
	'S_NOTIFICATIONS'=>			'통지',
	'S_NOTIFICATIONS_BIG'=>			'통지',
	'S_IT_NOTIFICATIONS'=>			'통지 보고서',

//	report5.php
        'S_TRIGGERS_TOP_100'=>			'실행 트리거 최상위 100항목',
	'S_TRIGGERS_TOP_100_BIG'=>		'실행 트리거 최상위 100항목',
	'S_NUMBER_OF_STATUS_CHANGES'=>		'상태 변경 횟수',
	'S_WEEK'=>				'주',
	'S_LAST'=>				'최근',
 
//	screenconf.php
	'S_SCREENS'=>				'스크린',
	'S_SCREEN'=>				'스크린',
	'S_CONFIGURATION_OF_SCREENS_BIG'=>	'스크린 설정',
	'S_CONFIGURATION_OF_SCREENS'=>		'스크린 설정',
	'S_SCREEN_ADDED'=>			'스크린을 추가하였습니다',
	'S_CANNOT_ADD_SCREEN'=>			'스크린을 추가할 수 없습니다',
	'S_SCREEN_UPDATED'=>			'스크린을 갱신하였습니다',
	'S_CANNOT_UPDATE_SCREEN'=>		'스크린을 갱신할 수 없습니다',
	'S_SCREEN_DELETED'=>			'스크린을 삭제하였습니다',
	'S_CANNOT_DELETE_SCREEN'=>		'스크린을 삭제할 수 없습니다',
	'S_COLUMNS'=>				'열',
	'S_ROWS'=>				'행',
	'S_NO_SCREENS_DEFINED'=>		'스크린이 정의되어 있지 않습니다',
	'S_DELETE_SCREEN_Q'=>			'스크린을 삭제하시겠습니까?',
	'S_CONFIGURATION_OF_SCREEN_BIG'=>	'스크린 설정',
	'S_SCREEN_CELL_CONFIGURATION'=>		'스크린 셀 설정',
	'S_RESOURCE'=>				'리소스',
	'S_RIGHTS_OF_RESOURCES'=>		'사용자 권한',
	'S_NO_RESOURCES_DEFINED'=>		'리소스가 정의되어 있지 않습니다',
	'S_SIMPLE_GRAPH'=>			'단순 그래프',
	'S_GRAPH_NAME'=>			'그래프 이름',
	'S_WIDTH'=>				'너비',
	'S_HEIGHT'=>				'높이',
	'S_CREATE_SCREEN'=>			'스크린 작성',
	'S_EDIT'=>				'편집',
	'S_DIMENSION_COLS_ROWS'=>		'크기(열x행)',

	'S_SLIDESHOWS'=>			'슬라이드쇼',
	'S_SLIDESHOW'=>				'슬라이드쇼',
	'S_CONFIGURATION_OF_SLIDESHOWS_BIG'=>	'슬라이드쇼 설정',
	'S_SLIDESHOWS_BIG'=>			'슬라이드쇼',
	'S_NO_SLIDESHOWS_DEFINED'=>		'슬라이드쇼가 정의되어 있지 않습니다',
	'S_COUNT_OF_SLIDES'=>			'슬라이드 수',
	'S_NO_SLIDES_DEFINED'=>			'슬라이드가 정의되어 있지 않습니다',
	'S_SLIDES'=>				'슬라이드',
	'S_NEW_SLIDE'=>				'새 슬라이드',

//	screenedit.php
	'S_MAP'=>				'맵',
	'S_AS_PLAIN_TEXT'=>			'일반 텍스트로 보기',
	'S_PLAIN_TEXT'=>			'일반 텍스트',
	'S_COLUMN_SPAN'=>			'열 합치기',
	'S_ROW_SPAN'=>				'행 합치기',
	'S_SHOW_LINES'=>			'표시할 행 수',
	'S_HOSTS_INFO'=>			'호스트 정보',
	'S_TRIGGERS_INFO'=>			'트리거 정보',
	'S_SERVER_INFO'=>			'서버 정보',
	'S_CLOCK'=>				'시각',
	'S_TRIGGERS_OVERVIEW'=>			'트리거 개요',
	'S_DATA_OVERVIEW'=>			'데이터 개요',
        'S_HISTORY_OF_ACTIONS'=>                '액션 이력',
        'S_HISTORY_OF_EVENTS'=>                 '이벤트 이력',

	'S_TIME_TYPE'=>				'사용할 시간',
	'S_SERVER_TIME'=>			'서버 시간',
	'S_LOCAL_TIME'=>			'로컬 시간',

	'S_STYLE'=>				'표시 형식',
	'S_VERTICAL'=>				'세로',
	'S_HORISONTAL'=>			'가로',

	'S_HORISONTAL_ALIGN'=>			'가로 위치',
	'S_LEFT'=>				'왼쪽',
	'S_CENTER'=>				'가운데',
	'S_RIGHT'=>				'오른쪽',

	'S_VERTICAL_ALIGN'=>			'세로 위치',
	'S_TOP'=>				'위쪽',
	'S_MIDDLE'=>				'가운데',
	'S_BOTTOM'=>				'아래쪽',

//	screens.php
	'S_CUSTOM_SCREENS'=>			'커스텀 스크린',
	'S_SCREENS_BIG'=>			'스크린',
	'S_NO_SCREENS_DEFINED'=>		'스크린이 정의되어 있지 않습니다',

	'S_SLIDESHOW_UPDATED'=>			'슬라이드쇼를 갱신하였습니다',
	'S_CANNOT_UPDATE_SLIDESHOW'=>		'슬라이드쇼를 갱신할 수 없습니다',
	'S_SLIDESHOW_ADDED'=>			'슬라이드쇼를 추가하였습니다',
	'S_CANNOT_ADD_SLIDESHOW'=>		'슬라이드쇼를 추가할 수 없습니다',
	'S_SLIDESHOW_DELETED'=>			'슬라이드쇼를 삭제하였습니다',
	'S_CANNOT_DELETE_SLIDESHOW'=>		'슬라이드쇼를 삭제할 수 없습니다',
	'S_DELETE_SLIDESHOW_Q'=>		'슬라이드쇼를 삭제하시겠습니까?',

//	services.php
	'S_ROOT_SMALL'=>			'root',
	'S_IT_SERVICE'=>			'IT 서비스',
	'S_IT_SERVICES'=>			'IT 서비스',
	'S_SERVICE_UPDATED'=>			'서비스를 갱신하였습니다',
	'S_NO_IT_SERVICE_DEFINED'=>		'IT 서비스가 정의되어 있지 않습니다',
	'S_CANNOT_UPDATE_SERVICE'=>		'서비스를 갱신할 수 없습니다',
	'S_SERVICE_ADDED'=>			'서비스를 추가하였습니다',
	'S_CANNOT_ADD_SERVICE'=>		'서비스를 추가할 수 없습니다',
	'S_SERVICE_DELETED'=>			'서비스를 삭제하였습니다',
	'S_CANNOT_DELETE_SERVICE'=>		'서비스를 삭제할 수 없습니다',
	'S_STATUS_CALCULATION'=>		'상태 계산',
	'S_STATUS_CALCULATION_ALGORITHM'=>	'상태 계산 알고리즘',
	'S_NONE'=>				'없음',
	'S_MAX_OF_CHILDS'=>			'최대 자식 수',
	'S_MIN_OF_CHILDS'=>			'최소 자식 수',
	'S_SOFT'=>				'Soft',
	'S_DO_NOT_CALCULATE'=>			'계산 안함',
	'S_SHOW_SLA'=>				'SLA 표시',
	'S_ACCEPTABLE_SLA_IN_PERCENT'=>		'SLA 허용 값(%)',
	'S_LINK_TO_TRIGGER_Q'=>			'트리거와 연결',
	'S_SORT_ORDER_0_999'=>			'정렬 순서(0->999)',
	'S_TRIGGER'=>				'트리거',
	'S_SERVER'=>				'서버',
	'S_DELETE'=>				'삭제',
	'S_CLONE'=>				'복제',
	'S_DELETE_SELECTED'=>			'선택 항목 삭제',
	'S_UPTIME'=>				'가동 시간',
	'S_DOWNTIME'=>				'정지 시간',
	'S_ONE_TIME_DOWNTIME'=>			'일시적인 정지 시간',
	'S_NO_TIMES_DEFINED'=>			'시간이 정의되어 있지 않습니다',
	'S_SERVICE_TIMES'=>			'서비스 시간',
	'S_NEW_SERVICE_TIME'=>			'새 서비스 시간',
	'S_NOTE'=>				'설명',
	'S_REMOVE'=>				'삭제',
	'S_DEPENDS_ON'=>			'의존 대상',

	'S_SUNDAY'=>				'일',
	'S_MONDAY'=>				'월',
	'S_TUESDAY'=>				'화',
	'S_WEDNESDAY'=>				'수',
	'S_THURSDAY'=>				'목',
	'S_FRIDAY'=>				'금',
	'S_SATURDAY'=>				'토',

//	srv_status.php
	'S_IT_SERVICES_BIG'=>			'IT 서비스',
	'S_SERVICE'=>				'서비스',
	'S_SERVICES'=>				'서비스',
	'S_REASON'=>				'이유',
	'S_SLA_LAST_7_DAYS'=>			'SLA(최근 7일)',

//	triggers.php
	'S_NO_TRIGGER'=>			'트리거가 없습니다',
	'S_NO_TRIGGERS_DEFINED'=>		'트리거가 정의되어 있지 않습니다',
	'S_NO_TRIGGER_DEFINED'=>		'트리거가 정의되어 있지 않습니다',
	'S_CONFIGURATION_OF_TRIGGERS'=>		'트리거 설정',
	'S_CONFIGURATION_OF_TRIGGERS_BIG'=>	'트리거 설정',
	'S_TRIGGERS_DELETED'=>			'트리거를 삭제하였습니다',
	'S_CANNOT_DELETE_TRIGGERS'=>		'트리거를 삭제할 수 없습니다',
	'S_TRIGGER_DELETED'=>			'트리거를 삭제하였습니다',
	'S_CANNOT_DELETE_TRIGGER'=>		'트리거를 삭제할 수 없습니다',
	'S_TRIGGER_ADDED'=>			'트리거를 추가하였습니다',
	'S_CANNOT_ADD_TRIGGER'=>		'트리거를 추가할 수 없습니다',
	'S_SEVERITY'=>				'심각도',
	'S_EXPRESSION'=>			'조건식',
	'S_DISABLED'=>				'비활성',
	'S_ENABLED'=>				'활성',
	'S_DISABLE_SELECTED'=>			'선택 항목 비활성화',
	'S_ENABLE_SELECTED'=>			'선택 항목 활성화',
	'S_ENABLE_SELECTED_TRIGGERS_Q'=>	'선택한 트리거를 활성화하시겠습니까?',
	'S_DISABLE_SELECTED_TRIGGERS_Q'=>	'선택한 트리거를 비활성화하시겠습니까?',
	'S_DELETE_SELECTED_TRIGGERS_Q'=>	'선택한 트리거를 삭제하시겠습니까?',
	'S_CHANGE'=>				'변경',
	'S_TRIGGER_UPDATED'=>			'트리거를 갱신하였습니다',
	'S_CANNOT_UPDATE_TRIGGER'=>		'트리거를 갱신할 수 없습니다',
	'S_DEPENDS_ON'=>			'의존 대상',
	'S_URL'=>				'URL',
	'S_CREATE_TRIGGER'=>			'트리거 작성',
	'S_INSERT'=>				'삽입',
	'S_SECONDS'=>				'초',
	'S_SEC_SMALL'=>				'초',
	'S_LAST_OF'=>				'최근',
	'S_SHOW_DISABLED_TRIGGERS'=>		'비활성 트리거 보이기',
	'S_HIDE_DISABLED_TRIGGERS'=>		'비활성 트리거 감추기',
        'S_THE_TRIGGER_DEPENDS_ON'=>            '다음 트리거에 의존',
        'S_NO_DEPENDENCES_DEFINED'=>            '의존 트리거가 없습니다',
        'S_NEW_DEPENDENCY'=>                    '의존 관계 작성',


//	tr_comments.php
	'S_TRIGGER_COMMENTS'=>			'트리거 주석',
	'S_TRIGGER_COMMENTS_BIG'=>		'트리거 주석',
	'S_COMMENT_UPDATED'=>			'주석을 갱신하였습니다',
	'S_CANNOT_UPDATE_COMMENT'=>		'주석을 갱신할 수 없습니다',
	'S_ADD'=>				'추가',

//	tr_status.php
	'S_STATUS_OF_TRIGGERS'=>		'트리거 상태',
	'S_STATUS_OF_TRIGGERS_BIG'=>		'트리거 상태',
	'S_SHOW_ACTIONS'=>			'액션 보이기',
	'S_SHOW_ALL_TRIGGERS'=>			'모든 트리거 보이기',
	'S_SHOW_DETAILS'=>			'상세 보이기',
	'S_SELECT'=>				'선택',
	'S_INVERSE_SELECT'=>                    '반전 선택',
	'S_TRIGGERS_BIG'=>			'트리거',
	'S_LAST_CHANGE'=>			'최근 갱신',
	'S_COMMENTS'=>				'주석',
	'S_ACKNOWLEDGED'=>			'인지 완료',
	'S_ACK'=>				'인지',
	'S_NEVER'=>				'Never',

//	users.php
	'S_ZABBIX_USER'=>			'ZABBIX 사용자',
	'S_ZABBIX_ADMIN'=>			'ZABBIX 관리자',
	'S_SUPER_ADMIN'=>			'ZABBIX 최고 관리자',
	'S_USER_TYPE'=>				'사용자 종류',
	'S_USERS'=>				'사용자',
	'S_USER_ADDED'=>			'사용자를 추가하였습니다',
	'S_CANNOT_ADD_USER'=>			'사용자를 추가할 수 없습니다',
	'S_CANNOT_ADD_USER_BOTH_PASSWORDS_MUST'=>'사용자를 추가할 수 없습니다. 암호가 일치하지 않습니다.',
	'S_USER_DELETED'=>			'사용자를 삭제하였습니다',
	'S_CANNOT_DELETE_USER'=>		'사용자를 삭제할 수 없습니다',
	'S_USER_UPDATED'=>			'사용자를 갱신하였습니다',
	'S_ONLY_FOR_GUEST_ALLOWED_EMPTY_PASSWORD'=>	'guest 사용자만이 빈 암호를 가질 수 있습니다.',
	'S_CANNOT_UPDATE_USER'=>		'사용자를 갱신할 수 없습니다',
	'S_CANNOT_UPDATE_USER_BOTH_PASSWORDS'=>	'사용자를 갱신할 수 없습니다. 암호가 일치하지 않습니다.',
	'S_GROUP_ADDED'=>			'그룹을 추가하였습니다',
	'S_CANNOT_ADD_GROUP'=>			'그룹을 추가할 수 없습니다',
	'S_GROUP_UPDATED'=>			'그룹을 갱신하였습니다',
	'S_CANNOT_UPDATE_GROUP'=>		'그룹을 갱신할 수 없습니다',
	'S_GROUP_DELETED'=>			'그룹을 삭제하였습니다',
	'S_CANNOT_DELETE_GROUP'=>		'그룹을 삭제할 수 없습니다',
	'S_CONFIGURATION_OF_USERS_AND_USER_GROUPS'=>'사용자 및 사용자 그룹 설정',
	'S_USER_GROUPS_BIG'=>			'사용자 그룹',
	'S_USERS_BIG'=>				'사용자',
	'S_USER_GROUPS'=>			'사용자 그룹',
	'S_MEMBERS'=>				'구성원',
	'S_TEMPLATES'=>				'템플릿',
	'S_NO_USER_GROUPS_DEFINED'=>		'사용자 그룹이 정의되어 있지 않습니다',
	'S_ALIAS'=>				'로그인 이름',
	'S_NAME'=>				'이름',
	'S_SURNAME'=>				'성',
	'S_IS_ONLINE_Q'=>			'로그인 상태',
	'S_NO_USERS_DEFINED'=>			'사용자가 정의되어 있지 않습니다',
	'S_RIGHT'=>				'오른쪽',
	'S_RIGHTS'=>				'권한',
	'S_NO_RIGHTS_DEFINED'=>			'권한이 정의되어 있지 않습니다',
	'S_READ_ONLY'=>				'읽기 전용',
	'S_READ_WRITE'=>			'쓰기 가능',
	'S_DENY'=>				'거부',
	'S_HIDE'=>				'감추기',
	'S_PASSWORD'=>				'암호',
	'S_CHANGE_PASSWORD'=>			'암호변경',
	'S_PASSWORD_ONCE_AGAIN'=>		'암호 확인',
	'S_URL_AFTER_LOGIN'=>			'로그인 후 URL',
	'S_AUTO_LOGOUT_IN_SEC'=>		'자동 로그아웃(초)',
	'S_SCREEN_REFRESH'=>                    '새로 고침(초)',
	'S_CREATE_USER'=>			'사용자 작성',
	'S_CREATE_GROUP'=>			'그룹 작성',
	'S_DELETE_SELECTED_USERS_Q'=>		'선택한 사용자를 삭제하시겠습니까?',
	'S_NO_ACCESSIBLE_RESOURCES'=>		'사용할 수 있는 리소스가 없습니다',

//	audit.php
	'S_AUDIT_LOG'=>				'감사 로그',
	'S_AUDIT_LOG_BIG'=>			'감사 로그',
	'S_ACTION'=>				'액션',
	'S_DETAILS'=>				'자세히',
	'S_UNKNOWN_ACTION'=>			'알 수 없는 액션',
	'S_ADDED'=>				'추가',
	'S_UPDATED'=>				'갱신',
	'S_MEDIA_TYPE'=>			'연락 방법',
	'S_GRAPH_ELEMENT'=>			'그래프 구성요소',
	'S_UNKNOWN_RESOURCE'=>			'알 수 없는 리소스',

//	profile.php
	'S_USER_PROFILE_BIG'=>			'사용자 프로파일',
	'S_USER_PROFILE'=>			'사용자 프로파일',
	'S_LANGUAGE'=>				'언어',
	'S_ENGLISH_GB'=>			'영어 (GB)',
	'S_FRENCH_FR'=>				'프랑스어 (FR)',
	'S_GERMAN_DE'=>				'독일어 (DE)',
	'S_ITALIAN_IT'=>			'이탈리아어 (IT)',
	'S_LATVIAN_LV'=>			'라트비아어 (LV)',
	'S_RUSSIAN_RU'=>			'러시아어 (RU)',
	'S_PORTUGUESE_PT'=>			'포르투갈어 (PT)',
	'S_SPANISH_SP'=>			'스페인어 (SP)',
	'S_SWEDISH_SE'=>			'스웨덴어 (SE)',
	'S_JAPANESE_JP'=>			'일본어 (JP)',
	'S_CHINESE_CN'=>			'중국어 (CN)',
	'S_DUTCH_NL'=>				'네덜란드어 (NL)',
	'S_HUNGARY_HU'=>                        '헝가리어 (HU)',
	'S_KOREAN_KR'=>				'한국어 (KR)',

//	index.php
	'S_ZABBIX_BIG'=>			'ZABBIX',

//	hostprofiles.php
	'S_HOST_PROFILES'=>			'호스트 프로파일',
	'S_HOST_PROFILES_BIG'=>			'호스트 프로파일',

//	popup.php
	'S_EMPTY'=>				'닫기',
	'S_STANDARD_ITEMS_BIG'=>		'표준 아이템',
	'S_NO_ITEMS'=>				'아이템 없음',

//	Menu

	'S_HELP'=>				'도움말',
	'S_PROFILE'=>				'프로파일',
	'S_GET_SUPPORT'=>			'서포트',
	'S_MONITORING'=>			'모니터링',
	'S_INVENTORY'=>				'목록',
	'S_QUEUE'=>				'큐',
	'S_EVENTS'=>				'이벤트',
	'S_EVENTS_BIG'=>			'이벤트',
	'S_MAPS'=>				'맵',
	'S_REPORTS'=>				'보고서',
	'S_GENERAL'=>				'일반',
	'S_AUDIT'=>				'감사',
	'S_LOGIN'=>				'로그인',
	'S_LOGOUT'=>				'로그아웃',
	'S_LATEST_DATA'=>			'최근 데이터',

//	Errors
	'S_INCORRECT_DESCRIPTION'=>		'이름이 적절하지 않습니다',
	'S_CANT_FORMAT_TREE'=>			'트리를 표시할 수 없습니다'
	);
?>
