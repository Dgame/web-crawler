mod crawler;

use crawler::Crawler;
use crawler::debug::*;

fn main() {
    let c = Crawler::new(Debug::new(DEBUG_ALL));

    c.spawn(vec![String::from("http://web.de/"),
                 String::from("http://gmx.de/"),
                 String::from("http://www.wikipedia.org/"),
                 String::from("http://www.zeit.de/news/index/"),
                 String::from("http://www.t-online.de/nachrichten/"),
                 String::from("http://www.focus.de/"),
                 String::from("http://www.n-tv.de/"),
                 String::from("http://www.weltderwunder.de/"),
                 String::from("http://www.zdnet.de/"),
                 String::from("http://www.it-business.de/"),
                 String::from("http://www.cnet.com/news/"),
                 String::from("http://www.pcwelt.de/"),
                 String::from("http://www.winfuture.de/"),
                 String::from("http://www.it-times.de/"),
                 String::from("http://www.heise.de/"),
                 String::from("http://www.golem.de/"),
                 String::from("http://www.reddit.com/"),
                 String::from("http://www.spieleprogrammierer.de/"),
                 String::from("http://www.dlang.org/")]);
}
