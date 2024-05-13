const URL = "./request.php?method=";
const ROOT_TITLE = "root";
const ROOT_MESSAGE = "Create root";
const CHILDREN_HIDE = "fa-angle-right";
const CHILDREN_SHOW = "fa-angle-down";
const ITEM_BTN_ADD = "fa-plus";
const ITEM_BTN_DELETE = "fa-minus";
const START_TIME = 30;

class Request {
  constructor() {
    this.method = "read";
    this.data = {};
  }

  create(data) {
    this.method = "create";
    this.data = data;
    return this;
  }

  update(id, data) {
    this.method = "update";
    this.data = { id, ...data };
    return this;
  }

  delete(id) {
    this.method = "delete";
    this.data = { id };
    return this;
  }

  execute() {
    return fetch(URL + this.method, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(this.data),
    }).then((response) => response.json());
  }
}

class App {
  constructor() {
    this.application = $("#app");
    this.modalAdd = $("#add");
    this.modalRemove = $("#remove");
    this.modalItemId;
    this.modalItemUpdate = false;
    this.timer;
  }

  request() {
    return new Request();
  }

  loading() {
    this.application
      .empty()
      .append('<i class="fa-solid fa-spinner fa-spin"></i>');
  }

  btnRoot(message = ROOT_MESSAGE) {
    this.application
      .empty()
      .append(
        '<span class="btn btn-primary" onclick="app.createRoot()">' +
          message +
          "</span>"
      );
  }

  callbackAfterExecute(data) {
    if (data && data.length > 0) {
      this.application
        .empty()
        .append(this.template(this.generateTreeFromItems(data)));
    } else {
      this.btnRoot();
    }
    this.closeModal();
  }

  createRequest(data) {
    this.loading();
    this.request()
      .create(data)
      .execute()
      .then((data) => this.callbackAfterExecute(data));
  }

  updateRequest(data) {
    this.loading();
    this.request()
      .update(this.modalItemId, data)
      .execute()
      .then((data) => this.callbackAfterExecute(data));
  }

  deleteRequest() {
    this.loading();
    this.request()
      .delete(this.modalItemId)
      .execute()
      .then((data) => this.callbackAfterExecute(data));
  }

  readItems() {
    this.loading();
    this.request()
      .execute()
      .then((data) => this.callbackAfterExecute(data));
  }

  generateTreeFromItems(params) {
    let values = Object.values(params);
    let map = {};

    values.forEach(function (row) {
      map[row.id] = {
        title: row.title,
        id: row.id,
        parent: row.parent_id,
        children: [],
      };
    });
    values.forEach(function (row) {
      if (map[row.parent_id]) {
        map[row.parent_id].children.push(map[row.id]);
      }
    });

    Object.keys(map).forEach((k) => {
      if (map[k].parent != 0) {
        delete map[k];
      }
    });

    return map;
  }

  template(params) {
    let li = [];
    Object.values(params).forEach((row) => {
      li.push(
        "<li>" +
          "<div>" +
          (row.children.length
            ? '<i class="fa-solid ' +
              CHILDREN_HIDE +
              '" onclick="app.toggleChildren(this)"></i>'
            : "") +
          '<span onclick="app.update(' +
          row.id +
          ", '" +
          row.title +
          "')\">" +
          row.title +
          "</span>" +
          '<div class="btn-group" role="group">' +
          '<span class="btn btn-outline-secondary btn-sm" onclick="app.add(' +
          row.id +
          ')"><i class="fa-solid ' +
          ITEM_BTN_ADD +
          '"></i></span>' +
          '<span class="btn btn-outline-secondary btn-sm" onclick="app.remove(' +
          row.id +
          ')"><i class="fa-solid ' +
          ITEM_BTN_DELETE +
          '"></i></span>' +
          "</div>" +
          "</div>" +
          (row.children.length ? this.template(row.children) : "") +
          "</li>"
      );
    });
    return li.length ? "<ul>" + li.join("") + "</ul>" : "";
  }

  openModal(modal_id, id) {
    var modal = new bootstrap.Modal(modal_id, {
      keyboard: false,
    });
    this.modalItemId = id;
    modal.show();

    this.startTimer();
  }

  closeModal() {
    $(".modal input").val("");
    $(".modal").modal("hide");
    clearInterval(this.timer);
    this.modalItemId = 0;
  }

  createRoot() {
    this.createRequest({
      title: ROOT_TITLE,
    });
  }

  add(id) {
    this.modalItemUpdate = false;
    this.modalAdd.find(".btn-primary").text("Add item");
    this.openModal(this.modalAdd, id);
  }

  addSubmit() {
    let data = {
      title: this.modalAdd.find("input").val(),
    };
    if (this.modalItemUpdate) {
      this.updateRequest(this.modalItemId, data);
    } else {
      data.parent_id = this.modalItemId;
      this.createRequest(data);
    }
  }

  remove(id) {
    this.openModal(this.modalRemove, id);
  }

  update(id, title) {
    this.modalItemUpdate = true;
    this.modalAdd.find(".btn-primary").text("Edit item");
    this.modalAdd.find("input").val(title);
    this.add(id);
  }

  startTimer() {
    let start = START_TIME;
    $(".timer").text(start);
    this.timer = setInterval(() => {
      start -= 1;
      $(".timer").text(start);
      if (start <= 0) {
        this.closeModal();
      }
    }, 1000);
  }

  toggleChildren(el) {
    $(el).closest("li").children("ul").toggle();
    if ($(el).hasClass(CHILDREN_HIDE)) {
      $(el).removeClass(CHILDREN_HIDE);
      $(el).addClass(CHILDREN_SHOW);
    } else {
      $(el).removeClass(CHILDREN_SHOW);
      $(el).addClass(CHILDREN_HIDE);
    }
  }
}

var app = new App();

window.onload = function () {
  app.readItems();
};
