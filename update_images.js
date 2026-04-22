const fs = require('fs');

const mappings = {
    "d1-1": "Beijing_Capital_International_Airport",
    "d1-2": "Hutong",
    "d1-3": "Peking_duck",
    "d2-1": "Forbidden_City",
    "d2-2": "Tiananmen_Square",
    "d3-1": "Mutianyu",
    "d3-2": "Wangfujing",
    "d4-1": "Beijing_South_railway_station",
    "d4-2": "West_Lake",
    "d4-3": "Qianjiang_New_City",
    "d5-1": "Wuzhen",
    "d6-1": "Shanghai_Hongqiao_railway_station",
    "d6-2": "Yu_Garden",
    "d6-3": "The_Bund",
    "d7-1": "Shanghai_Disneyland_Park",
    "d7-2": "Enchanted_Storybook_Castle",
    "d8-1": "Shanghai_Film_Park",
    "d8-2": "Oriental_Pearl_Tower",
    "d8-3": "Lujiazui",
    "d9-1": "Summer_Palace",
    "d9-2": "Former_French_Concession_(Shanghai)",
    "d10-1": "Humble_Administrator's_Garden",
    "d10-2": "Pingjiang_Road(Suzhou)",
    "d10-3": "Shantang_Street",
    "d11-1": "Shanghai_Pudong_International_Airport"
};

async function update() {
    let data = {};
    for (const [id, title] of Object.entries(mappings)) {
        try {
            const url = `https://en.wikipedia.org/api/rest_v1/page/summary/${title}`;
            const res = await fetch(url);
            const json = await res.json();
            if (json.originalimage && json.originalimage.source) {
                data[id] = json.originalimage.source;
            } else if (json.thumbnail && json.thumbnail.source) {
                // Remove the width restriction from the thumb URL to get full sz
                data[id] = json.thumbnail.source.replace(/\/\d+px-/, '/1000px-');
            } else {
                 data[id] = "https://picsum.photos/seed/" + id + "/1000/600";
            }
        } catch (e) {
            console.error("Error fetching", title);
            data[id] = "https://picsum.photos/seed/" + id + "/1000/600";
        }
    }
    fs.writeFileSync('data.json', JSON.stringify(data, null, 4));
    console.log("Updated data.json successfully with working image links.");
}

update();
