/*
 ** Zabbix
 ** Copyright (C) 2000-2012 Zabbix SIA
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
 ** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/


jQuery(function($) {
	'use strict';

	window.flickerfreeScreen = {

		screens: [],

		refresh: function(id) {
			var screen = this.screens[id];
			if (empty(screen.resourcetype)) {
				return;
			}

			var ajaxUrl = new Curl('jsrpc.php');
			ajaxUrl.setArgument('type', 9); // PAGE_TYPE_TEXT
			ajaxUrl.setArgument('method', 'screen.get');
			ajaxUrl.setArgument('mode', screen.mode);
			ajaxUrl.setArgument('flickerfreeScreenId', id);
			ajaxUrl.setArgument('screenitemid', screen.screenitemid);
			ajaxUrl.setArgument('hostid', screen.hostid);
			ajaxUrl.setArgument('profileIdx', !empty(screen.profileIdx) ? screen.profileIdx : null);
			ajaxUrl.setArgument('profileIdx2', !empty(screen.profileIdx2) ? screen.profileIdx2 : null);
			ajaxUrl.setArgument('period', !empty(screen.period) ? screen.period : null);
			ajaxUrl.setArgument('stime', !empty(screen.stime) ? screen.stime : null);

			// SCREEN_RESOURCE_GRAPH
			// SCREEN_RESOURCE_SIMPLE_GRAPH
			if (screen.resourcetype == 0 || screen.resourcetype == 1) {
				this.refreshImg(id, function() {
					$('#flickerfreescreen_' + id).find('a').each(function() {
						var chartUrl = new Curl($(this).attr('href'));
						chartUrl.setArgument('period', !empty(screen.period) ? screen.period : null);
						chartUrl.setArgument('stime', !empty(screen.stime) ? screen.stime : null);
						$(this).attr('href', chartUrl.getUrl());
					});
				});
			}

			// SCREEN_RESOURCE_MAP
			// SCREEN_RESOURCE_CHART
			else if (screen.resourcetype == 2 || screen.resourcetype == 18) {
				this.refreshImg(id);
			}

			// SCREEN_RESOURCE_HISTORY
			else if (screen.resourcetype == 17) {
				if (screen.data.action == 'showgraph') {
					this.refreshImg(id);
				}
				else {
					ajaxUrl.setArgument('resourcetype', !empty(screen.resourcetype) ? screen.resourcetype : null);
					ajaxUrl.setArgument('itemid', !empty(screen.data.itemid) ? screen.data.itemid : null);
					ajaxUrl.setArgument('action', !empty(screen.data.action) ? screen.data.action : null);
					ajaxUrl.setArgument('filter', !empty(screen.data.filter) ? screen.data.filter : null);
					ajaxUrl.setArgument('filter_task', !empty(screen.data.filterTask) ? screen.data.filterTask : null);
					ajaxUrl.setArgument('mark_color', !empty(screen.data.markColor) ? screen.data.markColor : null);

					this.refreshHtml(id, ajaxUrl);
				}
			}

			// SCREEN_RESOURCE_CLOCK
			else if (screen.resourcetype == 7) {
				// don't refresh anything
			}

			// others
			else {
				this.refreshHtml(id, ajaxUrl);
			}

			// set next refresh execution time
			if (screen.isFlickerfree && screen.refreshInterval > 0) {
				clearTimeout(screen.timeout);
				screen.timeout = window.setTimeout(function() { flickerfreeScreen.refresh(id); }, screen.refreshInterval);
			}
		},

		refreshAll: function(period, stime) {
			for (var id in this.screens) {
				if (empty(this.screens[id]) || empty(this.screens[id].resourcetype)) {
					continue;
				}

				this.screens[id].period = period;
				this.screens[id].stime = stime;

				// restart refresh execution starting from now
				clearTimeout(this.screens[id].timeout);
				this.refresh(id);
			}
		},

		refreshHtml: function(id, ajaxUrl) {
			var screen = this.screens[id];

			if (screen.isRefreshing) {
				screen.isReRefreshRequire = true;
			}
			else {
				screen.isRefreshing = true;
				var ajaxRequest = $.ajax({
					url: ajaxUrl.getUrl(),
					type: 'post',
					data: {},
					dataType: 'html',
					success: function(data) {
						var imgTotal = $(data).find('img').length;

						// preload images
						if (imgTotal > 0) {
							var loaded = 0;

							$(data).find('img').each(function() {
								$(this).load(function() {
									loaded++;

									if (imgTotal == loaded) {
										$('#flickerfreescreen_' + id).replaceWith(data);
										screen.isRefreshing = false;
									}
								});
							});
						}
						else {
							$('#flickerfreescreen_' + id).html(data);
							screen.isRefreshing = false;
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						screen.isRefreshing = false;
					}
				});

				$.when(ajaxRequest).always(function() {
					if (screen.isReRefreshRequire) {
						screen.isReRefreshRequire = false;
						flickerfreeScreen.refreshHtml(id, ajaxUrl);
						return;
					}
				});
			}
		},

		refreshImg: function(id, successAtion) {
			var screen = this.screens[id];

			if (screen.isRefreshing) {
				screen.isReRefreshRequire = true;
			}
			else {
				screen.isRefreshing = true;

				$('#flickerfreescreen_' + id).find('img').each(function() {
					var workImg = $(this);
					var doId = '#' + $(this).attr('id');
					var chartUrl = new Curl($(this).attr('src'));
					chartUrl.setArgument('period', !empty(screen.period) ? screen.period : null);
					chartUrl.setArgument('stime', !empty(screen.stime) ? screen.stime : null);
					chartUrl.setArgument('curtime', new CDate().getTime());

					// img
					$('<img />', {
						id: $(this).attr('id') + '_tmp',
						calss: $(doId).attr('class'),
						border: $(doId).attr('border'),
						usemap: $(doId).attr('usemap'),
						alt: $(doId).attr('alt'),
						name: $(doId).attr('name')
					}).attr('src', chartUrl.getUrl()).load(function() {
						var doId = $(this).attr('id').substring(0, $(this).attr('id').indexOf('_tmp'));

						$(this).attr('id', doId);
						$(workImg).replaceWith($(this));

						if (typeof(successAtion) !== 'undefined') {
							successAtion();
						}

						screen.isRefreshing = false;
					});
				});
			}
		},

		add: function(screen) {
			timeControl.refreshPage = false;

			this.screens[screen.id] = screen;
			this.screens[screen.id].refreshInterval = (screen.refreshInterval > 0) ? screen.refreshInterval * 1000 : 0;
			this.screens[screen.id].isRefreshing = false;
			this.screens[screen.id].isReRefreshRequire = false;

			if (screen.isFlickerfree && screen.refreshInterval > 0) {
				this.screens[screen.id].timeout = window.setTimeout(function() { flickerfreeScreen.refresh(screen.id); }, this.screens[screen.id].refreshInterval);
			}
		}
	};
});
