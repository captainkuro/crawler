/**
 * File: model.js
 * DB & Model Class declaration
 * DB Driver for MySQL and SQLite
 * @author captain_kuro
 */

// Constants used
App.Const.DB = {
	NOT		: "NOT",
	OR		: "OR",
	OR_NOT	: "OR NOT",
	AND		: "AND",
	AND_NOT	: "AND NOT",
	JOIN	: {
		NONE				: "JOIN",
		INNER				: "INNER JOIN",
		OUTER				: "OUTER JOIN",
		LEFT				: "LEFT JOIN",
		RIGHT				: "RIGHT JOIN",
		LEFT_OUTER			: "LEFT OUTER JOIN",
		RIGHT_OUTER			: "RIGHT OUTER JOIN",
		NATURAL				: "NATURAL JOIN",
		NATURAL_LEFT		: "NATURAL LEFT JOIN",
		NATURAL_RIGHT		: "NATURAL RIGHT JOIN",
		NATURAL_LEFT_OUTER	: "NATURAL LEFT OUTER JOIN",
		NATURAL_RIGHT_OUTER	: "NATURAL RIGHT OUTER JOIN",
	},
}

// Query Builder
/**
 * Class App.DB
 * the query builder combined with database driver for SQLite and MySQL
 * @param {String} dbConfigName	the database config name, default is "default"
 */
App.DB = function(dbConfigName) {
	if (dbConfigName == undefined || dbConfigName == null) dbConfigName = "default";
	this._select = [];
	this._from = [];
	this._join = [];
	this._where = [];
	this._groupby = [];
	this._having = [];
	this._orderby = [];
	this._distinct = false;
	this._limit = false;
	this._offset = false;
	this._set = {};
	if (App.Config.database[dbConfigName]["IMPLEMENTATION"] == "SQLite") {
		this._conn = new Jaxer.DB.SQLite.Connection(App.Config.database[dbConfigName]);
	} else {	// default is MySQL
		this._conn = new Jaxer.DB.MySQL.Connection(App.Config.database[dbConfigName]); 
	}
}

App.DB.prototype = {
	// Attributes:
	_conn		: null,		// the connection object
	_last		: "",		// last executed query
	// RAW SQL fragments:
	_select		: [],		// SELECT select_expr
	_from		: [],		// FROM table_references
	_join		: [],		// JOIN table_references join_constraint
	_where		: [],		// WHERE where_conditions
	_groupby	: [],		// GROUP BY {col_name | expr | position} [ASC | DESC], ... [WITH ROLLUP]
	_having		: [],		// HAVING where_conditions
	_orderby	: [],		// ORDER BY {col_name | expr | position} [ASC | DESC], ...]
	_distinct	: false,	// SELECT DISTINCT
	_limit		: false,	// LIMIT {[offset,] row_count | row_count OFFSET offset}
	_offset		: false,	// LIMIT {[offset,] row_count | row_count OFFSET offset}
	_set		: {},		// INSERT INTO [database.]table [(col_name,...)] VALUES (expr,...)
	
	// Methods:
	/**
	 * Escape a value
	 * @param {Null|String|Number} val
	 * @return {String}
	 */
	escapeValue: function(val) {
		if (val == undefined || val == null) {
			return "NULL";
		} else if (App.isBool(val)) {	// because SQLite doesn't have Boolean type
			if (val) {
				return "1";	// true becomes 1
			} else {
				return "0";	// false becomes 0
			}
		} else if (App.isNumber(val)) {
			return val.toString();
		} else if (App.isString(val)) {
			return "'" + Jaxer.Util.String.escapeForSQL(val) + "'";	// escape ' , \ , \r , \t dan \n
		} else if (App.isArray(val)) {
			var temp = "[";
			var first = true;
			for (var i in val) {
				if (first) {
					first = false;
				} else {
					temp += ",";
				}
				temp += this.escapeValue(val[i]);
			}
			temp += "]";
			return temp;
		} else {
			// undetermined type
			return "";
		}
	},
	
	/**
	 * Escape an identifier
	 * @param {String} val
	 * @return {String}
	 */
	escapeName: function(val) {
		/* Possible types of val:
		 * - regular, normal name:	book 				-> `book`
		 * - with dot: 				table.book 			-> `table`.`book`
		 * - with phrase AS: 		table.book AS title	-> `table`.`book` AS `title`
		 */
		var temp = "`" + 
			val.replace(/^\s+/, "").replace(/\s+$/, "")	// trim
				.replace(/\s*\.\s*/g, "`.`")			// escape "."
				.replace(/\s+[Aa][Ss]\s+/g, "` AS `") 	// escape " AS "
			+ "`";	// trim, then escape "." and " AS "
		return temp.replace(/`\*`/g, "*")	// change `*` into *
			.replace(/\s+DESC`$/i, "` DESC")// change  DESC` into ` DESC 
			.replace(/\s+ASC`$/i, "` ASC")	// change  ASC` into ` ASC
			;
	},
	
	/**
	 * Give items for SELECT statement
	 * @param {String|Array} names
	 * @param {Boolean} escaped if true then names will be escaped, default true
	 * @return {App.DB}
	 */
	select: function(names, escaped) {
		/* Possible types of names:
		 * - a string:	"book"
		 * - a string with comma:	"book,address"
		 * - an array:	["book","address"]
		 */
		if (escaped == undefined) escaped = true;
		if (!App.isArray(names)) {
			names = names.split(",");
		}
		if (escaped) {
			for (var i in names) {
				this._select.push(this.escapeName(names[i]));
			}
		} else {
			for (var i in names) {
				this._select.push(names[i]);
			}
		}
		return this;
	},
	
	/**
	 * Give item names for FROM statement
	 * @param {String|Array} names
	 * @return {App.DB}
	 */
	from: function(names) {
		/* Possible types of names:
		 * - a string:	"book"
		 * - a string with comma:	"book,address"
		 * - an array:	["book","address"]
		 */
		if (!App.isArray(names)) {
			names = names.split(",");
		}
		for (var i in names) {
			this._from.push(this.escapeName(names[i]));
		}
		return this;
	},
	
	/**
	 * Add JOIN fragment
	 * @param {String} name
	 * @param {String} condition	expr	not escaped automatically
	 * @param {App.Const.DB.JOIN} joinType	default "JOIN"
	 * @return {App.DB}
	 */
	join: function(name, condition, joinType) {
		if (joinType == undefined) {
			joinType = App.Const.DB.JOIN.NONE;	// default is just "JOIN"
		}
		this._join.push(joinType + " " + this.escapeName(name) + " ON " + condition);
		return this;
	},
	
	/**
	 * Add a WHERE segment
	 * @param {String} name 		column name, may end with comparison operator [=, <>, <, <=, >, >=] 
	 * @param {Number|String|Array} val	the value to be checked against
	 * @param {App.Const.DB} op		either NOT|OR|OR_NOT|AND|AND_NOT default AND
	 * @param {Boolean} escaped		if true then name and val will get escaped, default true
	 * @return {App.DB} 
	 */
	where: function(name, val, op, escaped) {
		if (op == undefined || op == null) op = App.Const.DB.AND;
		if (escaped == undefined || escaped == null) escaped = true;
		/* Possible types of name:
		 * - normal: "title"
		 * - with comparison operator: "title ="
		 * - with other operator: "title LIKE" or "year IN"
		 */
		name = name.replace(/^\s+/, "").replace(/\s+$/, "");	// trim name
		// cek whether name ends with operator or not
		var matches = name.match(/(\w+)\s+(IN|LIKE|=|\<\>|\<|\<=|\>|\>=)$/i);
		var coop = "=";	// default comp operator
		if (matches) {	// ends with comparison/other operator
			name = matches[1];	// extract the operator
			coop = matches[2];
		}
		if (escaped) {
			name = this.escapeName(name);
			val = this.escapeValue(val);
		}
		// if this is the first element or the first in parenthese, then op is not needed
		if (this._where.length == 0 || this._where[this._where.length - 1].match(/\($/)) {
			if (op != App.Const.DB.NOT) op = "";	// only NOT is allowed
		}
		this._where.push(op + " " + name + " " + coop + " " + val);
		return this;
	},
	
	/**
	 * Add an opening parenthese
	 * @param {App.Const.DB} op	either NOT|OR|OR_NOT|AND|AND_NOT default AND
	 * @return {App.DB}
	 */
	wherePOpen: function(op) {
		if (op == undefined || op == null) op = App.Const.DB.AND;
		if (this._where.length == 0 || this._where[this._where.length-1].match(/\($/)) {
			// if this is the first element or the first in parenthese, then op is not needed
			this._where.push("(");
		} else {
			this._where.push(op + " (");
		}
		return this;
	},
	
	/**
	 * Add a closing parenthese
	 * @return {App.DB}
	 */
	wherePClose: function() {
		this._where.push(")");	// simple, just add closing parenthese ")"
		return this;
	},
	
	/**
	 * Add GROUP BY statement
	 * @param {String|Array} names
	 * @return {App.DB}
	 */
	group: function(names) {
		/* Possible types of names:
		 * - a string:	"book"
		 * - a string with comma:	"book,address"
		 * - an array:	["book","address"]
		 */
		if (!App.isArray(names)) {
			names = names.split(",");
		}
		for (var i in names) {
			this._groupby.push(this.escapeName(names[i]));
		}
		return this;
	},

	/**
	 * Add a HAVING segment
	 * works exactly like where but using this._having
	 * @param {String} name 		column name, may end with comparison operator [=, <>, <, <=, >, >=] 
	 * @param {Number|String|Array} val	the value to be checked against
	 * @param {App.Const.DB} op		either NOT|OR|OR_NOT|AND|AND_NOT default AND
	 * @param {Boolean} escaped		if true then name and val will get escaped, default true
	 * @return {App.DB}
	 */
	having: function(name, val, op, escaped) {
		if (op == undefined || op == null) op = App.Const.DB.AND;
		if (escaped == undefined || escaped == null) escaped = true;
		/* Possible types of name:
		 * - normal: "title"
		 * - with comparison operator: "title ="
		 * - with other operator: "title LIKE" or "year IN"
		 */
		name = name.replace(/^\s+/, "").replace(/\s+$/, "");	// trim name
		// cek whether name ends with operator or not
		var matches = name.match(/(\w+)\s+(IN|LIKE|=|\<\>|\<|\<=|\>|\>=)$/i);
		var coop = "=";	// default comp operator
		if (matches) {	// ends with comparison/other operator
			name = matches[1];	// extract the operator
			coop = matches[2];
		}
		if (escaped) {
			name = this.escapeName(name);
			val = this.escapeValue(val);
		}
		// if this is the first element or the first in parenthese, then op is not needed
		if (this._having.length == 0 || this._having[this._having.length - 1].match(/\($/)) {
			if (op != App.Const.DB.NOT) op = "";	// only NOT is allowed
		}
		this._having.push(op + " " + name + " " + coop + " " + val);
		return this;
	},
	
	/**
	 * Add an opening parenthese
	 * @param {App.Const.DB} op	either NOT|OR|OR_NOT|AND|AND_NOT default AND
	 * @return {App.DB}
	 */
	havingPOpen: function(op) {
		if (op == undefined || op == null) op = App.Const.DB.AND;
		if (this._having.length == 0 || this._having[this._having.length-1].match(/\($/)) {
			// if this is the first element or the first in parenthese, then op is not needed
			this._having.push("(");
		} else {
			this._having.push(op + " (");
		}
		return this;
	},
	
	/**
	 * Add a closing parenthese
	 * @return {App.DB}
	 */
	havingPClose: function() {
		this._having.push(")");	// simple, just add closing parenthese ")"
		return this;
	},
	
	/**
	 * Add ORDER statement
	 * @param {String|Array} names
	 * @return {App.DB}
	 */
	order: function(names) {
		/* Possible types of names:
		 * - a string:	"book"
		 * - a string with comma:	"book,address"
		 * - an array:	["book","address"]
		 */
		if (!App.isArray(names)) {
			names = names.split(",");
		}
		for (var i in names) {
			this._orderby.push(this.escapeName(names[i]));
		}
		return this;
	},
	
	/**
	 * Assign offset to limit
	 * @param {Number} n
	 * @return {App.DB}
	 */
	offset: function(n) {
		if (App.isNumber(n)) {
			this._offset = n;
		} else if (App.isString(n)) {
			this._offset = parseInt(n);
		} else {
			// not a number nor string, do nothing
			this._offset = false;
		}
		return this;
	},
	
	/**
	 * Assign row limit
	 * @param {Number} n
	 * @return {App.DB}
	 */
	limit: function(n) {
		if (App.isNumber(n)) {
			this._limit = n;
		} else if (App.isString(n)) {
			this._limit = parseInt(n);
		} else {
			// not a number nor string, do nothing
			this._limit = false;
		}
		return this;
	},
	
	/**
	 * Setting the value to be inserted/updated
	 * if `name` is an Object with [name1: val1, name2: val2,...] then it will be used and second parameter will be ignored
	 * @param {String|Object} name 
	 * @param {String|Number|undefined} val
	 * @param {Boolean} escaped		if true then val will get escaped, default is true 
	 * @return {App.DB}
	 */
	set: function(name, val, escaped) {
		if (escaped == undefined) escaped = true;
		if (!App.isString(name)) {
			if (escaped) {
				for (var i in name) {
					this._set[i] = this.escapeValue(name[i]);
				}
			} else {
				for (var i in name) {
					this._set[i] = name[i];
				}
			}
		} else {
			if (escaped) {
				this._set[name] = this.escapeValue(val);
			} else {
				this._set[name] = val;
			}
		}
		return this;
	},
	
	/**
	 * Directly execute an SQL query
	 * and return the result
	 * @param {String} query
	 * @return {Jaxer.DB.ResultSet|Number|Object[]}
	 */
	execute: function(query) {
		this.last = query;
		return this._conn.execute(query);
	},
	
	/**
	 * Return the generated SQL SELECT query in string
	 * @return {String}
	 */
	buildQuery: function() {
		return "SELECT " + this.buildQuerySelect() + 
			" FROM " + this.buildQueryFrom() + 
			" WHERE " + this.buildQueryWhere();
	},
	
	/**
	 * Generate the SELECT part
	 * @return {String}
	 */
	buildQuerySelect: function() {
		var query = "";
		// SELECT ...
		if (this._distinct) {
			query += " DISTINCT ";
		}
		if (this._select.length > 0) {
			query += this._select.join(",");
		} else {
			query += "*";
		}
		return query;
	},
	
	/**
	 * Generate the FROM part
	 * @return {String}
	 */
	buildQueryFrom: function() {
		var query = "";
		// FROM ...
		query += this._from.join(",");
		if (this._join.length > 0) {
			// BETA
			query += " " + this._join.join(" ");
		}
		return query;
	},
	
	/**
	 * Generate the WHERE part
	 * @return {String}
	 */
	buildQueryWhere: function() {
		var query = "";
		// WHERE...
		if (this._where.length > 0) {
			// BETA TODO
			query += this._where.join(" ");
		} else {
			query += "1";
		}
		// GROUP BY ...
		if (this._groupby.length > 0) {
			query += " GROUP BY " + this._groupby.join(",");
			// BETA TODO
		}
		// HAVING ...
		if (this._having.length > 0) {
			query += " HAVING " + this._having.join(" ");
			// BETA TODO
		}
		// ORDER BY ...
		if (this._orderby.length > 0) {
			query += " ORDER BY " + this._orderby.join(",");
			// BETA TODO
		} 
		// LIMIT ...
		if (this._limit) {
			query += " LIMIT ";
			if (this._offset) {
				query += this._offset.toString() + ",";
			}
			query += this._limit.toString();
		}
		return query;
	},
	
	/**
	 * Return the last executed query
	 * @return {String}
	 */
	lastQuery: function() {
		return this._last;
	},
	
	/**
	 * Return the query building to empty state
	 */
	reset: function() {
		this._select = [];
		this._from = [];
		this._join = [];
		this._where = [];
		this._groupby = [];
		this._having = [];
		this._orderby = [];
		this._distinct = false;
		this._limit = false;
		this._offset = false;
		this._set = {};
	},
	
	/**
	 * Return the result of current SELECT query built
	 * @return {Jaxer.DB.ResultSet|null|Object[]}
	 */
	doGet: function() {
		try {
			this._last = this.buildQuery();
			this.reset();
			return this._conn.execute(this._last);
		} catch (e) {
			Jaxer.Log.error("App.DB Error executing SELECT: " + e.toString());
			return null;
		}
	},
	
	/**
	 * Return number of rows affected by the WHERE part of query
	 * @return {Number}
	 */
	doCount: function() {
		try {
			// BETA 
			this._last = "SELECT COUNT(*) AS countrow FROM " + this.buildQueryFrom() + " WHERE " + this.buildQueryWhere();
			var result = this._conn.execute(this._last);
			if (result) {
				return result.singleRow["countrow"];
			} else {
				return 0;
			}
		} catch (e) {
			Jaxer.Log.error("App.DB Error executing SELECT COUNT(): " + e.toString());
			return null;
		}
	},
	
	/**
	 * Execute the INSERT query
	 * then return lastInsertId
	 * @param {Object} data a key (column-name) <-> value (value) pair, if undefined then use this._set
	 * @param {Boolean} escaped		if true then the value will be escaped, default is true 
	 * @return {Number}
	 */
	doInsert: function(data, escaped) {
		if (escaped == undefined) escaped = true;
		try {
			if (data != undefined) {
				for (var i in data) {
					this.set(i, data[i], escaped);
				}
			}
			// Typical INSERT statement: INSERT INTO table_name (column1,...,columnN) VALUES (value1,...,valueN)
			// Build the columns string and values string
			var columns = "(";
			var values = "(";
			var first = true;
			for (var i in this._set) {
				if (first) {
					first = false;
				} else {
					columns += ",";
					values += ",";
				}
				columns += this.escapeName(i);	// column name hasn't been escaped
				values += this._set[i];			// value has been escaped before
			}
			columns += ")";
			values += ")";
			this._last = "INSERT INTO " + this.buildQueryFrom() + " " + columns + " VALUES " + values;
			this._conn.execute(this._last);
			// closing
			this.reset();
			return this._conn.getLastInsertId();
		} catch (e) {
			Jaxer.Log.error("App.DB Error executing INSERT: " + e.toString());
			return null;
		}
	},
	
	/**
	 * Execute the UPDATE query
	 * @return {Number} number of rows get effected
	 */
	doUpdate: function() {
		try {
			// Typical UPDATE statement: UPDATE table_name SET column1=value1,...,columnN=valueN WHERE where_condition
			var qset = "";
			var first = true;
			for (var i in this._set) {
				if (first) {
					first = false;
				} else {
					qset += ",";
				}
				qset += this.escapeName(i) + " = " + this._set[i];
			}
			this._last = "UPDATE " + this.buildQueryFrom() + " SET " + qset + " WHERE " + this.buildQueryWhere();
			// closing
			this.reset();
			return this._conn.execute(this._last);	// since i don't know what else to return :|
		} catch (e) {
			Jaxer.Log.error("App.DB Error executing UPDATE: " + e.toString());
			return null;
		}
	},
	
	/**
	 * Execute the DELETE query
	 * the condition must be defined already (FROM and WHERE)
	 * @return {Number} number of rows get effected
	 */
	doDelete: function() {
		try {
			this._last = "DELETE FROM " + this.buildQueryFrom() + " WHERE " + this.buildQueryWhere();
			this.reset();
			return this._conn.execute(this._last);
		} catch (e) {
			Jaxer.Log.error("App.DB Error executing DELETE: " + e.toString());
			return null;
		}
	},
}

App.Model = function(dbConfigName) {
	this._db = new App.DB(dbConfigName);
}

App.Model.prototype = {
	// Attributes:
	_db: null,
	
	// Methods:
	/**
	 * Return the database Driver
	 * @return {App.DB}
	 */
	db: function() {
		return this._db;
	},
	
	/**
	 * Return the last query executed
	 * @return {String}
	 */
	lastQuery: function() {
		return this._db.lastQuery();
	}
}