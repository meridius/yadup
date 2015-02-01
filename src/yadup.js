$(function() {
	
	/**
	 * Extension to JUSH - JavaScript Syntax Highlighter from jush.js 
	 * git://git.code.sf.net/p/jush/git
	 * Highlight text and preserve line endings
	 * @param {String} language Valid jush.highlight() language code
	 * @param {String} text Text to highlight
	 * @param {Integer} tab_width Number of spaces for tab, 0 for tab itself, defaults to 4
	 * @returns {String}
	 */
	jush.highlightTextPreserveLineEnd = function(language, text, tab_width) {
		this.last_tag = '';
		this.last_class = '';
		var tab = '';
		for (var i = (tab_width !== undefined ? tab_width : 4); i--; ) {
			tab += ' ';
		}
		var s = jush.highlight(language, jush.html_entity_decode(text.replace(/<br(\s+[^>]*)?>/gi, '\n').replace(/<[^>]*>/g, '')))
			.replace(/\t/g, tab.length ? tab : '\t').replace(/(^|\n| ) /g, '$1&nbsp;');
		return '<span class="jush-' + language + '">' + s.replace(/\n/g, '<br />') + '</span>'; // span - enable style for class="language-"
	};
	
	
	var Elements = {};
	Elements.updatesCount = $(".yadup-updates-count"); // overview span of updates to be done
	Elements.panel = $(".js-yadupPanel"); // whole panel
	Elements.controls = Elements.panel.find(".yadup-controls"); // left side with buttons
	Elements.codePanel = Elements.panel.find(".yadup-sqlPanel"); // right side with queries
	Elements.header = Elements.controls.find(".yadup-controls-header"); // table head for list
	Elements.list = Elements.controls.find(".yadup-controls-inner"); // list of found updates
	Elements.buttons = Elements.controls.find(".yadup-controls-buttons"); // control buttons
	Elements.code = Elements.codePanel.find("code");
	Elements.textarea = Elements.codePanel.find("textarea");
	Elements.pRun = Elements.codePanel.find(".yadup-sqlPanel-heading-run");
	Elements.pCreate = Elements.codePanel.find(".yadup-sqlPanel-heading-create");

	var SELECTED_ROW_NUMBER = 0;
	
	var Colors = {};
	Colors.none = "";
	Colors.lightYellow = "#ffffdb";
	Colors.orange = "orange";
	Colors.pink = "pink";
	Colors.lightgreen = "lightgreen";


	var Worker = function() {

		var _selectRow = function(rowNumber, tr) {
			Elements.list.find("tbody td").css("background", Colors.none);
			Elements.list.find("tbody td input[name='" + rowNumber + "']").closest("tr")
				.find("td").css("background", Colors.lightYellow);
			if (typeof tr !== "undefined") {
				tr.find("td").css("background", Colors.lightYellow);
			}
		};

		var _doUpdate = function(filenames, button, origLength, queriesCount) {
			var filename = filenames.shift();
			var link = button.data("link");
			var data = {
				"filename": filename
			};
			var tr = Elements.list
				.find("input:checked[data-filename='" + filename + "']")
				.closest("tr");
			var inputTd = tr.find("input:checked").closest("td");
			inputTd
				.find("input:checked").hide().end()
				.css("background", Colors.orange);
			$.post(link, data, function(payload) {
				inputTd.find("input:checked").show();
				if (!payload.state) {
					tr.find("td").css("background", Colors.pink);
					Elements.code.html(payload.message + jush.highlightTextPreserveLineEnd("sql", payload.sql));
					button.prop("disabled", false);
				} else {
					tr.find("td").css("background", Colors.lightgreen);
					queriesCount += payload.queriesDone;
					if (filenames.length > 0) {
						_doUpdate(filenames, button, origLength, queriesCount);
					} else {
						Elements.code.html("Total of " + origLength + " SQL files and " + queriesCount + " queries successfully ran.");
						button.prop("disabled", false);
					}
				}
			});
		};

		var showControls = function(group) {
			var createButtons = Elements.buttons.find("[name='cancel'], [name='saveFile']")
				.add(Elements.textarea);
			var runButtons = Elements.buttons.find("[name='runUpdates'], [name='createUpdate']")
				.add(Elements.code);
			if (group === "create") {
				Elements.list.find("tbody td").css("background", Colors.none);
				createButtons.show();
				runButtons.hide();
				Elements.pCreate.show();
				Elements.pRun.hide();
				Elements.textarea.focus();
			} else if (group === "run") {
				_selectRow(SELECTED_ROW_NUMBER);
				createButtons.hide();
				runButtons.show();
				Elements.pCreate.hide();
				Elements.pRun.show();
			}
		};

		var showAll = function(input) {
			var link = input.data("link");
			var data = {
				showAll: input.prop("checked")
			};
			$.post(link, data, function(payload) {
				SELECTED_ROW_NUMBER = 0;
				Elements.list.html(payload.table);
				Elements.updatesCount.html(payload.updatesCount);
			});
		};

		var invertCheckboxes = function(checkboxes) {
			checkboxes.prop("checked", function(i, val) {
				return !val;
			});
		};

		var showSelectedUpdate = function(tr) {
			SELECTED_ROW_NUMBER = tr.find("input").attr("name");
			Elements.code.html(function() {
				var sql = tr.find("input").data("sql");
				return (typeof sql === "undefined") ? "This update is not on disk." : jush.highlightTextPreserveLineEnd("sql", sql);
			});
			Elements.pRun.find("span").each(function(i, e) {
				var text = tr.find("td")[i].innerHTML;
				$(this).html(text);
			});
			showControls("run");
			_selectRow(SELECTED_ROW_NUMBER, tr);
		};

		var saveUpdateFile = function(button) {
			var link = button.data("link");
			var data = {
				"sql": Elements.textarea.val(),
				"isFull": Elements.pCreate.find("[name='is_full']").prop("checked")
			};
			$.post(link, data, function(payload) {
				if (!payload.state) {
					SELECTED_ROW_NUMBER = 0;
					Elements.code.html(payload.message);
				} else {
					if (SELECTED_ROW_NUMBER === 0) {
						Elements.code.html(payload.message);
					}
					Elements.list.html(payload.table);
					Elements.updatesCount.html(payload.updatesCount);
				}
				showControls("run");
			});
		};

		var runUpdates = function(button) {
			var filenames = new Array();
			Elements.list.find("input:checked").each(function() {
				filenames.push($(this).data("filename"));
			});

			Elements.list.find("tbody td").css("background", Colors.none);
			Elements.code.html("");
			if (filenames.length > 0) {
				button.prop("disabled", true);
				_doUpdate(filenames, button, filenames.length, 0);
			} else {
				Elements.code.html("Please select some SQL updates to run.");
			}
		};

		return {
			showControls: showControls,
			showAll: showAll,
			invertCheckboxes: invertCheckboxes,
			showSelectedUpdate: showSelectedUpdate,
			saveUpdateFile: saveUpdateFile,
			runUpdates: runUpdates
		};

	}();


	Elements.panel
		.on("change", "[name='show_all']", function() {
			Worker.showAll($(this));
		});

	Elements.header
		.on("click", "th:last", function() {
			Worker.invertCheckboxes(Elements.list.find("input[type='checkbox']"));
		});

	Elements.list
		.on("click", "tbody tr td:not(:has(> input))", function() {
			Worker.showSelectedUpdate($(this).closest("tr"));
		});

	Elements.buttons
		.on("click", "[name='createUpdate']", function() {
			Elements.textarea.val("");
			Worker.showControls("create");
		})
		.on("click", "[name='cancel']", function() {
			Worker.showControls("run");
		})
		.on("click", "[name='saveFile']", function() {
			Worker.saveUpdateFile($(this));
		})
		.on("click", "[name='runUpdates']", function() {
			Worker.runUpdates($(this));
	});


	Worker.showControls("run");

	
});