var V = function(x, y) {
	this.x = x;
	this.y = y;
};

V.prototype = {
	add: function(v) {
		return new V(this.x + v.x, this.y + v.y);
	},
	subtract: function(v) {
		return new V(this.x - v.x, this.y - v.y);
	},
	div: function(v) {
		return new V(this.x / v.x, this.y / v.y);
	},
	dot: function(v) {
		return new V(this.x * v.x, this.y * v.y);
	},
	dist: function() {
		return Math.sqrt(x * x + y * y);	
	},
	moveTowardsPoint: function(v, dist) {
		var relV = v.subtract(this),
			r = this.dist() + dist,
			phi = Math.atan2(relV.x, relV.y);
		return this.moveTowardsAngle(phi, r);
	},
	moveTowardsAngle: function(phi, r) {
		return new V(r * Math.cos(phi), r * Math.sin(phi)).add(this);
	}
};