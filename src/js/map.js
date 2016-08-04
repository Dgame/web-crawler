function() {
        for (var toNode in this["value"]["ps"]) {
            emit(toNode, {total: 0.0
                      , pr: this["value"]["ps"][toNode] * this["value"]["pr"]
                      , ps: { }
                      , diff: 0.0
                      , prevpr: 0.0});
        }
	
        emit(this["_id"], {total: this["value"]["total"]
                       , pr: 0.0
                       , ps : this["value"]["ps"]
                       , diff: 0.0
                       , prevpr: this["value"]["pr"]});
	
        emit(this["_id"], {total: 0.0 
                       , pr: 0.0
                       , ps : { } 
                       , diff: 0.0
                       , prevpr: 0.0});
};
