import './global.css'
import '@mantine/core/styles/Paper.css';
import '@mantine/core/styles/Text.css';
import '@mantine/core/styles/Card.css';
import '@mantine/core/styles/Grid.css';
import '@mantine/core/styles/SimpleGrid.css';
import '@mantine/core/styles/Group.css';
import '@mantine/core/styles/Progress.css';
import '@mantine/core/styles/Skeleton.css';
import { MantineProvider, Text, Progress, Card, Group, Box, SimpleGrid, Skeleton, Paper, rem } from '@mantine/core';
import { IconCalendarTime, IconSend } from '@tabler/icons-react';
import { __ } from '@wordpress/i18n';
import useSWR from "swr";
import classes from './reminders.module.css'

const fetcher = ([url,nonce]) => {
  let formData = new FormData();
  formData.append( "action", "cr_get_reminders_top_row_stats" );
  formData.append( "cr_nonce", nonce );
  return fetch(
    url,
    {
      method: "post",
      body: formData,
    }
  ).then(res => res.json());
};

function Reminders({ nonce, tab }) {

  let scheduledCard = {
    title: __( "Scheduled", "customer-reviews-woocommerce" ),
    count: <Skeleton height={20} width="60%" radius="sm" className={classes.skel}/>,
    descr: __( "Review invitations to be sent", "customer-reviews-woocommerce" ),
    channel: __( "Channel", "customer-reviews-woocommerce" ),
    channelSegm: <Skeleton height={15} width="100%" radius="sm" mt={3} className={classes.skel}/>,
    channelDescr: [
      <Skeleton width="100%" radius="sm" className={classes.skel+" "+classes.skelDescr}><Text fz="xs">...</Text><Text size="xs">...</Text></Skeleton>,
      <Skeleton width="100%" radius="sm" className={classes.skel+" "+classes.skelDescr}><Text fz="xs">...</Text><Text size="xs">...</Text></Skeleton>
    ]
  };

  let sentCard = {
    title: __( "Sent", "customer-reviews-woocommerce" ),
    count: <Skeleton height={20} width="60%" radius="sm" className={classes.skel}/>,
    descr: __( "Review invitations have been sent", "customer-reviews-woocommerce" ),
    sent: (
      <Box>
        <Skeleton width="100%" height={12} radius="sm" mt="xs" pt={5} pb={8} className={classes.skel}><Text fz="xs">...</Text></Skeleton>
        <Skeleton width="100%" height={12} radius="sm" mt="xs" pt={5} pb={8} className={classes.skel}><Text fz="xs">...</Text></Skeleton>
      </Box>
    )
  };

  if ('sent' === tab) {
    scheduledCard.class = classes.card;
    sentCard.class = classes.card + " " + classes.cardActive;
  } else {
    scheduledCard.class = classes.card + " " + classes.cardActive;
    sentCard.class = classes.card;
  }

  const { data, error, isLoading } = useSWR(
    [ajaxurl,nonce],
    fetcher
  );
  if (error) return "An error has occurred.";
  if ( -1 == data ) return "Nonce has expired. Please refresh the page."
  if ( -2 == data ) return "No permissions to view the charts."

  if ( !error && !isLoading ) {
    scheduledCard.count = <Text className={classes.value}>{data.scheduled}</Text>;
    const segments = data.channels.map((segment) => (
      <Progress.Section value={segment.part} className={classes[segment.class]} key={segment.label}>
        {segment.part > 10 && <Progress.Label fz="10">{segment.part}%</Progress.Label>}
      </Progress.Section>
    ));
    scheduledCard.channelSegm = (
      <Progress.Root size={15} classNames={{ root: classes.progressWithSegments, label: classes.progressLabel }} mt={3} bg="#E1E1E1">
        {segments}
      </Progress.Root>
    );
    scheduledCard.channelDescr = data.channels.map((stat) => (
      <Box key={stat.label} className={classes.stat+' '+classes[stat.class]}>
        <Text tt="uppercase" fz="xs" c="dimmed" fw={700}>
          {stat.label}
        </Text>

        <Group justify="space-between" align="flex-end" className={classes.channelDesc}>
          <Text fw={600} size="xs">{stat.count}</Text>
          <Text fw={700} size="xs" className={classes.statCount+' '+classes[stat.class]}>
            {stat.part}%
          </Text>
        </Group>
      </Box>
    ));

    sentCard.count = <Text className={classes.value}>{data.sent}</Text>;

    sentCard.sent = data.statuses.map((ratio, i) => (
      <Box key={ratio.label} mt="xs" className={classes.progressBox}>
        <Group justify="space-between">
          <Text fz="xs">
            {ratio.label}
          </Text>
          <Text fz="xs">
            {ratio.part}%
          </Text>
        </Group>
        <Progress value={ratio.part} mt={5} classNames={{ section: classes[ratio.class] }} bg="#E1E1E1"/>
      </Box>
    ));
  }

  return (
    <MantineProvider>
      <SimpleGrid cols={{ base: 1, xs: 2 }} className={classes.topGrid}>
        <Card withBorder padding="xs" className={scheduledCard.class}>
          <Group justify="space-between">
            <Text size="xs" c="dimmed" className={classes.title}>
              {scheduledCard.title}
            </Text>
            <IconCalendarTime size="1.4rem" stroke={1.5} className={classes.icon} />
          </Group>

          <Group align="flex-end" mt={20}>
            {scheduledCard.count}
          </Group>

          <Text c="dimmed" fz="xs" mt="7">
            {scheduledCard.descr}
          </Text>

          <Text fz="xs" mt="xs">
            {scheduledCard.channel}
          </Text>

          {scheduledCard.channelSegm}

          <SimpleGrid cols={2} mt="xs">
            {scheduledCard.channelDescr}
          </SimpleGrid>
        </Card>
        <Card withBorder padding="xs" className={sentCard.class}>
          <Group justify="space-between">
            <Text size="xs" c="dimmed" className={classes.title}>
              {sentCard.title}
            </Text>
            <IconSend size="1.4rem" stroke={1.5} className={classes.icon} />
          </Group>

          <Group align="flex-end" mt={20}>
            {sentCard.count}
          </Group>

          <Text c="dimmed" fz="xs" mt="7">
            {sentCard.descr}
          </Text>

          {sentCard.sent}
        </Card>
      </SimpleGrid>
    </MantineProvider>
  );
}

export default Reminders
