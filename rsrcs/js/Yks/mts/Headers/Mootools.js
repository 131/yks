
Doms.loaders['path://mt/Drag/Drag.js'] = {
    "class": "Drag",
    "match": false,
    "descr": "Drag"
};

Doms.loaders['path://mt/Drag/Drag.Move.js'] = {
    "class": "Drag.Move",
    "match": false,
    "descr": "Drag.move",
    "deps" : ["path://mt/Drag/Drag.js"],
    "patch": ["path://patch/Drag/Drag.Move.js"]
};

Doms.loaders['path://mt/Interface/Accordion.js'] = {
    "class": "Accordion",
    "match": false,
    "patch": ["path://patch/Interface/Accordion.js"]
};


Doms.loaders['path://mt/Utilities/Cookie.js'] = {
    "class": "Cookie",
    "match": false,
    "descr": "Cookie utilities",
    "patch": ["path://patch/Utilities/Cookie.js"]
};

Doms.loaders['path://mt/Interface/Slider.js'] = {
    "class": "Slider",
    "match": false,
    "descr": "Slider"
};

Doms.loaders['path://mt/Fx/Fx.Scroll.js'] = {
    "class": "Fx.Scroll",
    "match": false,
    "descr": "Effect to smoothly scroll any element, including the window."
};
