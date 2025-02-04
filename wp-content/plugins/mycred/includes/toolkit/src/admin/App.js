import React, { useState, useEffect } from "react";
import {
  DialogActions,
  Dialog,
  DialogContent,
  DialogTitle,
  AppBar,
  Toolbar,
  Typography,
  TextField,
  Button,
  InputAdornment,
  IconButton,
  Box,
  FormControl,
  Grid,
  Card,
  CardContent,
  CardActions,
  Snackbar,
  Skeleton,
} from "@mui/material";
import { createTheme, ThemeProvider } from "@mui/material/styles";
import CssBaseline from "@mui/material/CssBaseline";
import SearchIcon from "@mui/icons-material/Search";
import { __ } from "@wordpress/i18n";
import Switch from "@mui/material/Switch";
import FormControlLabel from "@mui/material/FormControlLabel";
import SettingsIcon from "@mui/icons-material/Settings";
import CloseIcon from "@mui/icons-material/Close";
import { ReactComponent as MyCredLogo } from "./icons/mycred-logo.svg";
import { ReactComponent as MyCredDialogLogo } from "./icons/mycred-dialog-logo.svg";
import { ReactComponent as UpgradeVector } from "./icons/upgrade-vector.svg";
import { ReactComponent as BackgroundSVG } from "./icons/popup-background.svg";
import { styled } from "@mui/material/styles";
import CheckCircleIcon from "@mui/icons-material/CheckCircle";
import "@fontsource/figtree"; 
import "@fontsource/figtree/700.css";

import addOnsData from "./addons.json";


const theme = createTheme({
  palette: {
    primary: { main: "#4A90E2" },
    secondary: { main: "#E64A19" },
  },
  typography: { fontFamily: "Roboto, sans-serif" },
});

const categories = [
  "All",
  "ECommerce",
  "LMS",
  "Community",
  "Membership",
  "buyCred Gateways",
  "cashCred Gateways",
  "Others"
];

function contains(data, value) {
    if (Array.isArray(data)) {
        return data.includes(value); // Check if the array includes the value
    } else if (data && typeof data === "object") {
        return Object.values(data).includes(value); // Check if the object values include the value
    }
    return false; // Return false for other data types
}

const App = () => {
  const [snackbarOpen, setSnackbarOpen] = useState(false);
  const [snackbarMessage, setSnackbarMessage] = useState("");
  const [loading, setLoading] = useState(true);
  const [Addons, setAddons] = useState([]);
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedCategory, setSelectedCategory] = useState("All");
  const [open, setOpen] = useState(false);
  const [addonsData, setAddonsData] = useState(addOnsData);
  
  const handleOpen = () => {
    setOpen(true);
  };

  const ToggleSwitch = styled(Switch)(({ theme }) => ({
    width: 42,
    height: 20,
    padding: 0,
    display: "flex",
    "&:active": {
      "& .MuiSwitch-thumb": {
        width: 15,
      },
      "& .MuiSwitch-switchBase.Mui-checked": {
        transform: "translateX(22px)",
      },
    },
    "& .MuiSwitch-switchBase": {
      padding: 2,
      "&.Mui-checked": {
        transform: "translateX(22px)",
        color: "#fff",
        "& + .MuiSwitch-track": {
          opacity: 1,
          backgroundColor: "#5F2CED",
        },
      },
    },
    "& .MuiSwitch-thumb": {
      boxShadow: "0 2px 4px 0 rgb(0 35 11 / 20%)",
      width: 16,
      height: 16,
      borderRadius: 16 / 2,
      transition: theme.transitions.create(["width"], {
        duration: 200,
      }),
    },
    "& .MuiSwitch-track": {
      borderRadius: 20 / 2,
      opacity: 1,
      backgroundColor: "#E0E0E0",
      boxSizing: "border-box",
    },
  }));

  const handleClose = () => {
    setOpen(false);
  };

  const fetchAddOns = async () => {
    try {
      setLoading(true);
      const siteUrl = `${window.location.origin}/wp-json/mycred-toolkit/v1/get-addons`;

      const response = await fetch(siteUrl, {
        method: "GET",
        headers: {
          'X-WP-Nonce': window.mycredAddonsData.nonce,
          "Content-Type": "application/json",
        },
      });

      if (!response.ok) {
        throw new Error("Network response was not ok");
      }

      const Addons = await response.json();
      setAddons(Addons);
    } catch (error) {
      setSnackbarMessage("Error fetching add-ons: " + error.message);
      setSnackbarOpen(true);
    } finally {
      setLoading(false);
    }
  };

  const checkProaddonsfile = async () => {
    try {
        setLoading(true);
        const siteUrl = `${window.location.origin}/wp-json/mycred-toolkit/v1/check-addons-files`;

        // Filter the 'pro' type add-ons from addonsData
        const proAddOns = addonsData.filter((addon) => addon.type === "pro");

        // Send request with pro add-ons data
        const response = await fetch(siteUrl, {
            method: "POST",
            headers: {
                'X-WP-Nonce': window.mycredAddonsData.nonce,
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                proAddOns: proAddOns,
            }),
        });

        if (!response.ok) {
            throw new Error("Network response was not ok");
        }

        // Parse the response
        const Addons = await response.json();

        // Update the pro add-ons in addonsData with the status from the response
        addonsData.forEach( (addon) => {
            // Find the matching addon in Addons by slug
            const matchingAddon = Addons.find((item) => item.slug === addon.slug);

            if (matchingAddon) {
              
                addon.status = matchingAddon.status;

            } 
        });

    
    } catch (error) {
       // console.error("Error fetching add-ons: ", error.message);
    } finally {
        setLoading(false);
    }
  };

  
  useEffect(() => {
    fetchAddOns();

    checkProaddonsfile();

    if (window.mycredAddonsData && Array.isArray(window.mycredAddonsData.addons)) {
        setAddonsData( window.mycredAddonsData.addons );
    } 

  }, []);

  const handleToggleClick = async (addOn) => {
    if ( addOn.status == 'locked' ) {
      handleOpen();
      return;
    }

    if (loading) return;

    setLoading(true);
    try {
      const siteUrl = `${window.location.origin}/wp-json/mycred-toolkit/v1/enable-addon`;

      const response = await fetch(siteUrl, {
        method: "POST",
        headers: {
          'X-WP-Nonce': window.mycredAddonsData.nonce,
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          addOnSlug: addOn.slug,
          addOnTitle: addOn.title,
          dependency: addOn.dependency,
          dependencyName: addOn.dependencyName,
        }),
      });

      const result = await response.json();
      fetchAddOns();
      setSnackbarMessage(result.message);
      setSnackbarOpen(true);
    } catch (error) {
      setSnackbarOpen(true);
    } finally {
      setLoading(false);
    }
  };

  const handleSearchData = (event) => {
    setSearchTerm(event.target.value);
  };

  const handleCategoryChange = (newCategory) => {
    setSelectedCategory(newCategory);
  };

  const renderSVG = (iconSlug) => {
    try {
      const IconComponent = require(`./icons/${iconSlug}.svg`).default;

      if (IconComponent.startsWith("data:image/svg+xml")) {
        return (
          <div
            dangerouslySetInnerHTML={{
              __html: atob(IconComponent.split(",")[1]),
            }}
          />
        );
      }

      // If it's recognized as a React component
      return <IconComponent width={24} height={24} />;
    } catch (error) {
      console.error(`SVG not found for icon name: ${iconSlug}`);
      return null;
    }
  };

  const filteredAddons = addonsData
    .filter((addOn) =>
      addOn.title.toLowerCase().includes(searchTerm.toLowerCase())
    )
    .filter(
      (addOn) =>
        selectedCategory === "All" || addOn.category === selectedCategory
    );

  return (
    <ThemeProvider theme={theme}>
      <CssBaseline />

      <Box sx={{ flexGrow: 1 }}>
        <AppBar
          color="default"
          elevation={0}
          sx={{
            boxShadow: "0px 4px 8.4px 0px rgba(94, 44, 237, 0.06)",
            border: "none",
            position: "static",
            backgroundColor: "#FFFFFF",
          }}
        >
          <Toolbar>
            <Typography
              variant="h4"
              sx={{
                flexGrow: 1,
                display: "flex",
                alignItems: "center",
                gap: "8px",
              }}
            >
              <MyCredLogo />
            </Typography>


            {window.mycredAddonsData.upgraded && (
              <Button
                variant="outlined"
                sx={{
                  border: "none",
                  fontSize: "14px",
                  fontWeight: "600",
                  boxShadow: "none",
                  backgroundColor: "#FFD79C",
                  color: "#7A5323",
                  textTransform: "capitalize",
                  padding: "7px 21px",
                  display: "flex",
                  alignItems: "center",
                  gap: "8px",
                }}
                onClick={handleOpen}
              >
                <UpgradeVector />
                {__("Upgrade Now", "mycred-toolkit")}
              </Button>
            )}
           
            <Dialog
              open={open}
              onClose={handleClose}
              sx={{
                "& .MuiDialog-paper": {
                  width: "602px",
                  height: "379px",
                  borderRadius: "16px",
                  boxShadow: "0 4px 20px rgba(0, 0, 0, 0.1)",
                  backgroundColor: "#2D1572",
                  position: "relative",
                  overflow: "hidden",
                  display: "flex",
                  flexDirection: "column",
                  justifyContent: "center",
                  alignItems: "center",
                  padding: 0,
                },
              }}
            >
              {/* Background SVG */}
              <BackgroundSVG
                style={{
                  position: "absolute",
                  top: 0,
                  left: 0,
                  width: "100%",
                  height: "100%",
                  zIndex: 0,
                }}
              />

              <DialogTitle
                sx={{
                  backgroundColor: "transparent",
                  color: "#fff",
                  fontSize: "28px",
                  fontWeight: "600",
                  textAlign: "center",
                  position: "relative",
                  padding: "16px",
                  width: "100%",
                  display: "flex",
                  justifyContent: "center",
                  alignItems: "center",
                  zIndex: 1,
                  fontFamily: "'Figtree', sans-serif", 
                }}
              >
                {/* Centered Logo */}
                <MyCredDialogLogo
                  style={{
                    marginTop: "15px",
                  }}
                />
                <IconButton
                  aria-label="close"
                  onClick={handleClose}
                  sx={{
                    position: "absolute",
                    right: 8,
                    top: 8,
                    color: "#fff",
                  }}
                >
                  <CloseIcon />
                </IconButton>
              </DialogTitle>

              <DialogContent
                sx={{
                  textAlign: "center",
                  padding: "20px 30px",
                  fontSize: "18px",
                  display: "flex",
                  flexDirection: "column",
                  alignItems: "center",
                  justifyContent: "center",
                  width: "100%",
                  zIndex: 1,
                  fontFamily: "'Figtree', sans-serif", // Apply Figtree font
                }}
              >
                <Typography
                  variant="h6"
                  sx={{
                    fontWeight: "bold",
                    fontSize: "22px",
                    marginBottom: "16px",
                    color: "#fff",
                    fontFamily: "'Figtree', sans-serif", // Apply Figtree font
                  }}
                >
                  <span style={{ color: "#FFA500" }}>Join Over 10,000+</span>{" "}
                  WordPress Site Owners to Gamify User Engagement
                </Typography>
                <Typography
                  variant="body1"
                  sx={{
                    fontSize: "16px",
                    color: "#FFFFFF",
                    marginBottom: "20px",
                    fontFamily: "'Figtree', sans-serif",
                  }}
                >
                  Get more from your WordPress site by upgrading to myCred Pro!
                </Typography>
              </DialogContent>

              <DialogActions
                sx={{
                  justifyContent: "center",
                  paddingBottom: "20px",
                  width: "100%",
                  zIndex: 1,
                }}
              >
                <Button
                onClick={() => {
                  window.location.href = "https://mycred.me/pricing/";
                }}
                variant="contained"
                sx={{
                  borderRadius: "45px",
                  backgroundColor: "#F19C38",
                  color: "#341883",
                  fontWeight: "bold",
                  marginBottom: "30px",
                  padding: "10px 20px",
                  fontSize: "14px",
                  fontFamily: "'Figtree', sans-serif", 
                  "&:hover": {
                    backgroundColor: "#FF9800",
                  },
                }}
              >
                Get myCred Pro
              </Button>
              </DialogActions>
            </Dialog>

            <TextField
              variant="outlined"
              placeholder="Search"
              value={searchTerm}
              onChange={handleSearchData}
              sx={{
                padding: "14px",
              }}
              InputProps={{
                startAdornment: (
                  <InputAdornment
                    position="start"
                    sx={{ color: "#036666" }}
                  ></InputAdornment>
                ),
                sx: {
                  "& .MuiOutlinedInput-notchedOutline": {
                    border: "none",
                  },
                },
              }}
            />
          </Toolbar>
        </AppBar>
      </Box>

      <Box
        sx={{
          padding: 4,
          backgroundColor: "#F0F4FF",
        }}
      >
        <Typography
          variant="h5"
          sx={{
            fontWeight: "500",
            flexGrow: 1,
            display: "flex",
            alignItems: "center",
            gap: "8px",
          }}
        >
          {__("Popular Add-ons, New Possibilities.", "mycred-toolkit")}
        </Typography>
        <br />

        <Typography
          variant="p"
          sx={{
            fontWeight: "400",
            color: "#9698C2",
            flexGrow: 1,
            display: "flex",
            alignItems: "center",
            gap: "8px",
          }}
        >
          {__(
            "myCred is an easy-to-use WordPress gamification plugin. Seamlessly create a daily login rewards",
            "mycred-toolkit"
          )}
          <br />
          {__(
            "program or gamify your website and increase the average customer value with less marketing effort.",
            "mycred-toolkit"
          )}
        </Typography>

        <br />

        <Grid
          container
          spacing={2}
          sx={{
            marginBottom: 3,
            flexWrap: "nowrap",
            overflowX: "auto",
          }}
        >
          {categories.map((category) => (
            <Grid item key={category}>
              <Button
                onClick={() => handleCategoryChange(category)}
                sx={{
                  border: "none",
                  fontSize: "14px",
                  fontWeight: "500",
                  backgroundColor:
                    selectedCategory === category ? "#EBE4FF" : "#FFFFFF",
                  color: selectedCategory === category ? "#7C54F1" : "#9698C2",
                }}
                variant="outlined"
              >
                {category}
              </Button>
            </Grid>
          ))}
        </Grid>

      <Grid container spacing={3}>
      {filteredAddons.map((addOn) => (
        <Grid item xs={12} sm={6} md={4} key={addOn.slug}>
          <Card
            sx={{
              width: "100%",
              height: "auto",
              position: "relative",
              borderRadius: "8px",
              border: "1px solid transparent",
              display: "flex",
              flexDirection: "column",
            }}
          >
            {/* Card Content */}
            <CardContent>
              {loading ? (
                <>
                  <Box
                    display="flex"
                    justifyContent="space-between"
                    alignItems="flex-start"
                    mb={2}
                  >
                    <Skeleton variant="circular" width={40} height={40} />
                    <Skeleton variant="circular" width={24} height={24} />
                  </Box>
                  <Skeleton variant="text" width="50%" height={32} />
                  <Skeleton variant="text" width="80%" />
                  <Skeleton variant="text" width="60%" />
                </>
              ) : (
                <>
                  <Box
                    display="flex"
                    justifyContent="space-between"
                    alignItems="flex-start"
                    mb={2}
                  >
                    <Box>{renderSVG(addOn.slug)}</Box>

                    <IconButton
                      size="small"
                      aria-label="settings"
                      sx={{ alignSelf: "flex-start" }}
                      disabled={! contains(Addons, addOn.slug) || addOn.status === "locked"} // Disable if locked
                    >
                      <SettingsIcon
                        onClick={() => {
                          if (contains(Addons, addOn.slug) && addOn.status !== "locked") {
                            window.location.href = `${window.location.origin}/${addOn.settingUrl}`;
                          }
                        }}
                        fontSize="small"
                        sx={{
                          cursor: contains(Addons, addOn.slug) && addOn.status !== "locked"
                            ? "pointer"
                            : "not-allowed",
                        }}
                      />
                    </IconButton>
                  </Box>

                  <Typography sx={{ color: "#2D1572" }} variant="h6" mb={1}>
                    {addOn.title}
                  </Typography>

                  <Typography variant="body2" mb={2}>
                    {addOn.description}
                  </Typography>
                </>
              )}
            </CardContent>
            
            <Box
              sx={{
                backgroundColor: "#F6F9FF",
                display: "flex",
                alignItems: "center",
                justifyContent: "space-between",
                padding: "16px",
              }}
            >
             <Typography
                component="a"
                onClick={() => window.open(addOn.addonUrl, "_blank", "noopener,noreferrer")}
                variant="body2"
                color="primary"
                sx={{
                  color: "#9496C1",
                  textDecoration: "none",
                  cursor: "pointer",
                }}
              >
                {__("Learn More", "mycred-toolkit")}
              </Typography>

            <FormControlLabel
              control={
                <ToggleSwitch
                  checked={contains(Addons, addOn.slug)}
                  onChange={() => handleToggleClick(addOn)}
                  disabled={loading}
                  sx={{
                    marginRight: "16px",
                  }}
                />
              }
              label={
                loading
                  ? "Loading..."
                  : contains(Addons, addOn.slug)
                  ? "Enable"
                  : "Disable"
              }
              labelPlacement="start"
              sx={{
                marginLeft: "10px",
                gap: "10px",
                color: contains(Addons, addOn.slug) ? "#5F2CED" : "#9496C1",
              }}
            />

            </Box>
          </Card>
        </Grid>
      ))}
    </Grid>
      </Box>

      <Snackbar
        open={snackbarOpen}
        onClose={() => setSnackbarOpen(false)}
        autoHideDuration={6000}
        anchorOrigin={{ vertical: "bottom", horizontal: "right" }}
        sx={{
          "& .MuiSnackbarContent-root": {
            backgroundColor: "green",
            color: "#fff",
            display: "flex",
            alignItems: "center",
            fontSize: "16px",
          },
        }}
        message={
          <Box display="flex" alignItems="center">
            <CheckCircleIcon sx={{ mr: 1, color: "#fff" }} />
            <Typography>{snackbarMessage}</Typography>
          </Box>
        }
      />
    </ThemeProvider>
  );
};

export default App;
