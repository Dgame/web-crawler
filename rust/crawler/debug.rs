#[allow(dead_code)]
pub const DEBUG_NONE: u8 = 0x0;
pub const DEBUG_SPAWN: u8 = 0x1;
pub const DEBUG_URL: u8 = 0x2;
pub const DEBUG_STATUS: u8 = 0x4;
pub const DEBUG_OUTPUT: u8 = 0x8;
#[allow(dead_code)]
pub const DEBUG_ALL: u8 = DEBUG_SPAWN | DEBUG_URL | DEBUG_STATUS | DEBUG_OUTPUT;

pub struct Debug {
    debug_level: u8
}

impl Debug {
    pub fn new(debug_level: u8) -> Debug {
        Debug { debug_level: debug_level }
    }

    pub fn debug(&self, message: String) {
        println!("{}", message);
    }

    pub fn debug_spawn(&self, message: String) {
        if self.debug_level & DEBUG_SPAWN != 0 {
            self.debug(message);
        }
    }

    pub fn debug_url(&self, message: String) {
        if self.debug_level & DEBUG_URL != 0 {
            self.debug(message);
        }
    }

    pub fn debug_status(&self, message: String) {
        if self.debug_level & DEBUG_STATUS != 0 {
            self.debug(message);
        }
    }

    pub fn debug_output(&self, message: String) {
        if self.debug_level & DEBUG_OUTPUT != 0 {
            self.debug(message);
        }
    }
}
