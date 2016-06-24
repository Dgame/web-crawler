function() {
    var coll = db.pages;
    emit(this.url, this.pr, coll.count);
}
