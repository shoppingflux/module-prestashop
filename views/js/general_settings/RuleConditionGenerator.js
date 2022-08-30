/*
 *
 *  Copyright since 2019 Shopping Feed
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Academic Free License (AFL 3.0)
 *  that is bundled with this package in the file LICENSE.md.
 *  It is also available through the world-wide-web at this URL:
 *  https://opensource.org/licenses/AFL-3.0
 *  If you did not receive a copy of the license and are unable to
 *  obtain it through the world-wide-web, please send an email
 *  to tech@202-ecommerce.com so we can send you a copy immediately.
 *
 *  @author    202 ecommerce <tech@202-ecommerce.com>
 *  @copyright Since 2019 Shopping Feed
 *  @license   https://opensource.org/licenses/AFL-3.0  Academic Free License (AFL 3.0)
 *
 */

var RuleConditionGenerator = function(conf) {
  this.btnNewCondition = null;
  this.container = null;
  this.conditionSetContainer = null;
  this.translations = (conf.translations !== undefined ? conf.translations : null);
  this.conditionsBoard = null;
};

RuleConditionGenerator.prototype.buildElement = function(conf) {
  if (conf.type === undefined) {
    return null;
  }

  var element = document.createElement(conf.type);

  if (conf.attributes !== undefined) {
    for (var attribute in conf.attributes) {
      element.setAttribute(attribute, conf.attributes[attribute]);
    }
  }

  if (conf.text !== undefined) {
    element.appendChild(document.createTextNode(conf.text));
  }

  return element;
};

RuleConditionGenerator.prototype.init = function(container) {
  if (!(container instanceof Element)) {
    return false;
  }

  this.container = container;
  this.conditionSetContainer = this.buildElement({
    type: 'div',
    attributes: {
      'condition-set-container': 1
    }
  });
  this.container.appendChild(this.conditionSetContainer);
  this.btnNewCondition = this.buildElement({
    type: 'span',
    attributes: {
      class: 'btn btn-default'
    },
  });
  this.btnNewCondition.appendChild(this.buildElement({
    type: 'i',
    attributes: {
      class: 'icon-plus-sign'
    }
  }));
  this.btnNewCondition.appendChild(this.buildElement({
    type: 'span',
    text: 'Add a new condition group'
  }));
  this.container.appendChild(this.btnNewCondition);
  this.registerEventListeners();
};

RuleConditionGenerator.prototype.registerEventListeners = function() {
  this.btnNewCondition.addEventListener('click', function() {
    if (this.conditionsBoard === null) {
      this.addConditionsBoard();
    }

    this.addNewConditionSet();
    this.conditionSetContainer.addEventListener('click', function(event) {
      if (event.target.classList.contains('.panel')) {
        this.focusOnSet(event.target)
      }

      var panel = event.target.closest('.panel');

      if (panel) {
        this.focusOnSet(panel);
      }
    }.bind(this));
  }.bind(this));
};

RuleConditionGenerator.prototype.addNewConditionSet = function() {
  var set = this.buildElement({
    type: 'div',
    attributes: {
      class: 'panel',
      'id-group': (this.conditionSetContainer.querySelectorAll('.panel').length + 1)
    }
  });
  set.appendChild(this.buildElement({
    type: 'div',
    attributes: {
      class: 'panel-heading'
    },
    text: 'Condition group'
  }));
  var table = this.buildElement({
    type: 'table',
    attributes: {
      class: 'table alert-info'
    }
  });
  var head = this.buildElement({
    type: 'thead'
  });
  var tr = this.buildElement({
    type: 'tr'
  });
  tr.appendChild(this.buildElement({
    type: 'th',
    attributes: {
      class: 'fixed-width-md'
    },
    text: 'Type'
  }));
  tr.appendChild(this.buildElement({
    type: 'th',
    text: 'Value'
  }));
  tr.appendChild(this.buildElement({
    type: 'th'
  }));
  head.appendChild(tr);
  table.appendChild(head);
  table.appendChild(this.buildElement({
    type: 'tbody'
  }));
  set.appendChild(table);
  this.conditionSetContainer.appendChild(set);
  this.focusOnSet(set);
};

RuleConditionGenerator.prototype.addConditionsBoard = function() {
  this.conditionsBoard = this.buildElement({
    type: 'div',
    attributes: {
      class: 'panel'
    }
  });
  this.container.appendChild(this.conditionsBoard);
  this.conditionsBoard.appendChild(this.buildElement({
    type: 'div',
    attributes: {
      class: 'panel-heading'
    },
    text: 'Conditions'
  }));

  this.getCategoryList()
    .then(function(list) {
      var group = this.buildElement({
        type: 'div',
        attributes: {
          class: 'form-group',
          'category-condition': 1
        }
      });
      group.appendChild(this.buildElement({
        type: 'label',
        attributes: {
          class: 'control-label col-lg-3'
        },
        text: 'Category'
      }));
      var wrapper = this.buildElement({
        type: 'div',
        attributes: {
          class: 'col-lg-9'
        }
      });
      var selectWrapper = this.buildElement({
        type: 'div',
        attributes: {
          class: 'col-lg-8'
        }
      });
      selectWrapper.appendChild(this.buildSelectFromList(list, 'id_category'));
      wrapper.appendChild(selectWrapper);

      var buttonWrapper = this.buildElement({
        type: 'div',
        attributes: {
          class: 'col-lg-1'
        }
      });
      var button = this.buildElement({
        type: 'span',
        attributes: {
          class: 'btn btn-default'
        }
      });
      button.addEventListener('click', this.addCategoryCondition.bind(this));
      button.appendChild(this.buildElement({
        type: 'i',
        attributes: {
          class: 'icon-plus-sign'
        }
      }));
      button.appendChild(this.buildElement({
        type: 'span',
        text: 'Add condition'
      }));
      buttonWrapper.appendChild(button);
      wrapper.appendChild(buttonWrapper);
      group.appendChild(wrapper);
      this.conditionsBoard.appendChild(group);
    }.bind(this))
    .then(function () {
      return this.getBrandList();
    }.bind(this))
    .then(function(list) {
      var group = this.buildElement({
        type: 'div',
        attributes: {
          class: 'form-group'
        }
      });
      group.appendChild(this.buildElement({
        type: 'label',
        attributes: {
          class: 'control-label col-lg-3',
          'brand-condition': 1
        },
        text: 'Brand'
      }));
      var wrapper = this.buildElement({
        type: 'div',
        attributes: {
          class: 'col-lg-9'
        }
      });
      var selectWrapper = this.buildElement({
        type: 'div',
        attributes: {
          class: 'col-lg-8'
        }
      });
      selectWrapper.appendChild(this.buildSelectFromList(list, 'id_brand'));
      wrapper.appendChild(selectWrapper);

      var buttonWrapper = this.buildElement({
        type: 'div',
        attributes: {
          class: 'col-lg-1'
        }
      });
      var button = this.buildElement({
        type: 'span',
        attributes: {
          class: 'btn btn-default'
        }
      });
      button.appendChild(this.buildElement({
        type: 'i',
        attributes: {
          class: 'icon-plus-sign'
        }
      }));
      button.appendChild(this.buildElement({
        type: 'span',
        text: 'Add condition'
      }));
      buttonWrapper.appendChild(button);
      wrapper.appendChild(buttonWrapper);
      group.appendChild(wrapper);
      this.conditionsBoard.appendChild(group);
    }.bind(this))
    .then(function() {
      return this.getSupplierList();
    }.bind(this))
    .then(function(list) {
      var group = this.buildElement({
        type: 'div',
        attributes: {
          class: 'form-group',
          'supplier-condition': 1
        }
      });
      group.appendChild(this.buildElement({
        type: 'label',
        attributes: {
          class: 'control-label col-lg-3'
        },
        text: 'Supplier'
      }));
      var wrapper = this.buildElement({
        type: 'div',
        attributes: {
          class: 'col-lg-9'
        }
      });
      var selectWrapper = this.buildElement({
        type: 'div',
        attributes: {
          class: 'col-lg-8'
        }
      });
      selectWrapper.appendChild(this.buildSelectFromList(list, 'id_supplier'));
      wrapper.appendChild(selectWrapper);

      var buttonWrapper = this.buildElement({
        type: 'div',
        attributes: {
          class: 'col-lg-1'
        }
      });
      var button = this.buildElement({
        type: 'span',
        attributes: {
          class: 'btn btn-default'
        }
      });
      button.appendChild(this.buildElement({
        type: 'i',
        attributes: {
          class: 'icon-plus-sign'
        }
      }));
      button.appendChild(this.buildElement({
        type: 'span',
        text: 'Add condition'
      }));
      buttonWrapper.appendChild(button);
      wrapper.appendChild(buttonWrapper);
      group.appendChild(wrapper);
      this.conditionsBoard.appendChild(group);
    }.bind(this))
    .then(function() {
      return this.getAttributeGroupList();
    }.bind(this))
    .then(function(list) {
      var group = this.buildElement({
        type: 'div',
        attributes: {
          class: 'form-group',
          'attribute-condition': 1
        }
      });
      group.appendChild(this.buildElement({
        type: 'label',
        attributes: {
          class: 'control-label col-lg-3'
        },
        text: 'Attribute'
      }));
      var wrapper = this.buildElement({
        type: 'div',
        attributes: {
          class: 'col-lg-9'
        }
      });
      var selectWrapper = this.buildElement({
        type: 'div',
        attributes: {
          class: 'col-lg-4'
        }
      });
      selectWrapper.appendChild(this.buildSelectFromList(list, 'id_attribute_group'));
      wrapper.appendChild(selectWrapper);

      group.appendChild(wrapper);
      this.conditionsBoard.appendChild(group);
    }.bind(this))
    .then(function() {
      return this.getAttributeList(document.querySelector('select[name="id_attribute_group"]').value);
    }.bind(this))
    .then(function(list) {
      var selectWrapper = this.buildElement({
        type: 'div',
        attributes: {
          class: 'col-lg-4'
        }
      });
      selectWrapper.appendChild(this.buildSelectFromList(list, 'id_attribute'));
      document.querySelector('[attribute-condition] .col-lg-9').appendChild(selectWrapper);

      var buttonWrapper = this.buildElement({
        type: 'div',
        attributes: {
          class: 'col-lg-1'
        }
      });
      var button = this.buildElement({
        type: 'span',
        attributes: {
          class: 'btn btn-default'
        }
      });
      button.appendChild(this.buildElement({
        type: 'i',
        attributes: {
          class: 'icon-plus-sign'
        }
      }));
      button.appendChild(this.buildElement({
        type: 'span',
        text: 'Add condition'
      }));
      buttonWrapper.appendChild(button);
      document.querySelector('[attribute-condition] .col-lg-9').appendChild(buttonWrapper);

    }.bind(this))
    .then(function() {
      return this.getFeatureList();
    }.bind(this))
    .then(function(list) {
      var group = this.buildElement({
        type: 'div',
        attributes: {
          class: 'form-group',
          'feature-condition': 1
        }
      });
      group.appendChild(this.buildElement({
        type: 'label',
        attributes: {
          class: 'control-label col-lg-3'
        },
        text: 'Feature'
      }));
      var wrapper = this.buildElement({
        type: 'div',
        attributes: {
          class: 'col-lg-9'
        }
      });
      var selectWrapper = this.buildElement({
        type: 'div',
        attributes: {
          class: 'col-lg-4'
        }
      });
      selectWrapper.appendChild(this.buildSelectFromList(list, 'id_feature'));
      wrapper.appendChild(selectWrapper);

      group.appendChild(wrapper);
      this.conditionsBoard.appendChild(group);
    }.bind(this))
    .then(function() {
      return this.getFeatureValueList(document.querySelector('select[name="id_feature"]').value)
    }.bind(this))
    .then(function(list) {
      var selectWrapper = this.buildElement({
        type: 'div',
        attributes: {
          class: 'col-lg-4'
        }
      });
      selectWrapper.appendChild(this.buildSelectFromList(list, 'id_feature_value'));
      document.querySelector('[feature-condition] .col-lg-9').appendChild(selectWrapper);

      var buttonWrapper = this.buildElement({
        type: 'div',
        attributes: {
          class: 'col-lg-1'
        }
      });
      var button = this.buildElement({
        type: 'span',
        attributes: {
          class: 'btn btn-default'
        }
      });
      button.appendChild(this.buildElement({
        type: 'i',
        attributes: {
          class: 'icon-plus-sign'
        }
      }));
      button.appendChild(this.buildElement({
        type: 'span',
        text: 'Add condition'
      }));
      buttonWrapper.appendChild(button);
      document.querySelector('[feature-condition] .col-lg-9').appendChild(buttonWrapper);

    }.bind(this));
};

RuleConditionGenerator.prototype.getCategoryList = function() {
  var url = new URL(location.toString());
  url.searchParams.append('ajax', 1);
  url.searchParams.append('action', 'GetCategoryList');

  return fetch(url.toString())
    .then(function(response) {
      return response.json();
    });
};

RuleConditionGenerator.prototype.getBrandList = function() {
  var url = new URL(location.toString());
  url.searchParams.append('ajax', 1);
  url.searchParams.append('action', 'GetBrandList');

  return fetch(url.toString())
    .then(function(response) {
      return response.json();
    });
};

RuleConditionGenerator.prototype.getSupplierList = function() {
  var url = new URL(location.toString());
  url.searchParams.append('ajax', 1);
  url.searchParams.append('action', 'GetSupplierList');

  return fetch(url.toString())
    .then(function(response) {
      return response.json();
    });
};

RuleConditionGenerator.prototype.getAttributeGroupList = function() {
  var url = new URL(location.toString());
  url.searchParams.append('ajax', 1);
  url.searchParams.append('action', 'GetAttributeGroupList');

  return fetch(url.toString())
    .then(function(response) {
      return response.json();
    });
};

RuleConditionGenerator.prototype.getAttributeList = function(idGroup) {
  var url = new URL(location.toString());
  url.searchParams.append('ajax', 1);
  url.searchParams.append('action', 'GetAttributeList');
  url.searchParams.append('id_group', idGroup);

  return fetch(url.toString())
    .then(function(response) {
      return response.json();
    });
};

RuleConditionGenerator.prototype.getFeatureList = function() {
  var url = new URL(location.toString());
  url.searchParams.append('ajax', 1);
  url.searchParams.append('action', 'GetFeatureList');

  return fetch(url.toString())
    .then(function(response) {
      return response.json();
    });
};

RuleConditionGenerator.prototype.getFeatureValueList = function(idFeature) {
  var url = new URL(location.toString());
  url.searchParams.append('ajax', 1);
  url.searchParams.append('action', 'GetFeatureValueList');
  url.searchParams.append('id_feature', idFeature);

  return fetch(url.toString())
    .then(function(response) {
      return response.json();
    });
};

RuleConditionGenerator.prototype.buildSelectFromList = function (list, name) {
  var select = this.buildElement({
    type: 'select',
    attributes: {
      name: name
    }
  });

  for (var key in list) {
    var option = this.buildElement({
      type: 'option',
      attributes: {
        value: list[key]['id']
      },
      text: list[key]['title']
    });
    select.appendChild(option);
  }

  return select;
};

RuleConditionGenerator.prototype.focusOnSet = function(set) {
  this.conditionSetContainer.querySelectorAll('.alert-info').forEach(function(element) {
    element.classList.remove('alert-info');
  });
  set.querySelector('.panel-heading').classList.add('alert-info');
  set.querySelector('table').classList.add('alert-info');
};

RuleConditionGenerator.prototype.addCondition = function(conf) {
  var tr = this.buildElement({
    type: 'tr'
  });
  var button = this.buildElement({
    type: 'span',
    attributes: {
      class: 'btn btn-default'
    }
  });
  var buttonWrapper = this.buildElement({
    type: 'td'
  });

  tr.appendChild(this.buildElement({
    type: 'td',
    text: (conf.type !== undefined ? conf.type : '')
  }));
  tr.appendChild(this.buildElement({
    type: 'td',
    text: (conf.value !== undefined ? conf.value : '')
  }));
  button.appendChild(this.buildElement({
    type: 'i',
    attributes: {
      class: 'icon-remove'
    }
  }));
  button.appendChild(this.buildElement({
    type: 'span',
    text: 'Delete'
  }));
  buttonWrapper.appendChild(button);
  tr.appendChild(buttonWrapper);
  document.querySelector('table.alert-info tbody').appendChild(tr);
};

RuleConditionGenerator.prototype.addCategoryCondition = function() {
  var value = document.querySelector('select[name="id_category"] option:checked').innerHTML;
  var id = document.querySelector('select[name="id_category"]').value;
  this.addCondition({
    id: id,
    value: value,
    type: 'Category'
  });
};

